"""Initialize once, resume the disposable Thoth API and renew its scoped credentials."""
import datetime
import fcntl
import json
import os
from pathlib import Path
import signal
import socket
import subprocess
import sys

from common import graphql, request, wait_for

STATE = Path(os.environ.get("THOTH_STATE_DIR", "/state"))
CLIENT = Path(os.environ.get("THOTH_CLIENT_DIR", "/client"))
ZITADEL = os.environ.get("ZITADEL_URL", "http://zitadel:8080")
API = "http://127.0.0.1:8000"


def save(path, value):
    # Readers never see a partially written credential file.
    temporary = path.with_suffix('.tmp')
    with open(temporary, "w", opener=lambda p, flags: os.open(p, flags, 0o600)) as target:
        json.dump(value, target)
    temporary.replace(path)


def bootstrap():
    path = STATE / "bootstrap.json"
    if path.exists():
        return json.loads(path.read_text())
    if (STATE / "initializing").exists() or (STATE / "ready").exists():
        raise RuntimeError("Incomplete or legacy bootstrap: run down --apply, then prepare --dataset PATH --apply")
    (STATE / "initializing").touch(mode=0o600)
    pat = wait_for("bootstrap PAT", lambda: (STATE / "admin.pat").read_text().strip() or None)
    print("Configuring disposable Zitadel project", flush=True)
    setup = subprocess.run(["thoth", "zitadel", "setup"], env=dict(os.environ, THOTH_PAT=pat),
                           capture_output=True, text=True, timeout=120)
    if setup.returncode:
        raise RuntimeError("Thoth Zitadel setup failed (output withheld because it can contain keys)")
    keys = [line.split("=", 1)[1] for line in setup.stdout.splitlines() if line.startswith("PRIVATE_KEY=")]
    if len(keys) != 1 or not keys[0]:
        raise RuntimeError("Bootstrap did not return exactly one API key")
    management = ZITADEL + "/management/v1"
    org = wait_for("Zitadel management readiness", lambda: request(management + "/orgs/me", pat))["org"]["id"]
    projects = request(management + "/projects/_search", pat, {})["result"]
    project = next(p["id"] for p in projects if p["name"] == "Thoth")
    request(management + f"/projects/{project}/roles", pat,
            {"roleKey": "WORK_LIFECYCLE", "displayName": "Work lifecycle"})
    user = request(management + "/users/machine", pat,
                   {"userName": "omp-tests", "name": "OMP tests",
                    "accessTokenType": "ACCESS_TOKEN_TYPE_BEARER"})["userId"]
    request(management + f"/users/{user}/grants", pat,
            {"projectId": project, "roleKeys": ["PUBLISHER_USER", "WORK_LIFECYCLE"]})
    identity = {"privateKey": keys[0], "userId": user, "orgId": org}
    save(path, identity)
    (STATE / "initializing").unlink()
    return identity


def refresh_credentials():
    with open(STATE / "refresh.lock", "w") as lock:
        fcntl.flock(lock, fcntl.LOCK_EX)
        identity = json.loads((STATE / "bootstrap.json").read_text())
        pat = (STATE / "admin.pat").read_text().strip()
        fixture_path = STATE / "fixture.json"
        fixture = json.loads(fixture_path.read_text()) if fixture_path.exists() else {}
        if not fixture.get("publisherId"):
            fixture["publisherId"] = graphql(API, pat,
                'mutation($data: NewPublisher!) { createPublisher(data: $data) { publisherId } }',
                {"data": {"publisherName": "Cypress Publisher", "zitadelId": identity["orgId"]}}
            )["createPublisher"]["publisherId"]
            save(fixture_path, fixture)
        if not fixture.get("imprintId"):
            fixture["imprintId"] = graphql(API, pat,
                'mutation($data: NewImprint!) { createImprint(data: $data) { imprintId } }',
                {"data": {"publisherId": fixture["publisherId"], "imprintName": "Cypress Imprint"}}
            )["createImprint"]["imprintId"]
            save(fixture_path, fixture)
        path = CLIENT / "client.json"
        previous = json.loads(path.read_text()) if path.exists() else {}
        now = datetime.datetime.now(datetime.timezone.utc)
        if previous and datetime.datetime.fromisoformat(previous["expiresAt"]) > now + datetime.timedelta(hours=1):
            try:
                graphql(API, previous["token"], "{ me { userId } }")
                return
            except (OSError, RuntimeError):
                pass
        expiry = (now + datetime.timedelta(days=2)).isoformat()
        endpoint = ZITADEL + f'/management/v1/users/{identity["userId"]}/pats'
        token = request(endpoint, pat, {"expirationDate": expiry})
        credentials = dict(fixture, url=os.environ["THOTH_GRAPHQL_API"], token=token["token"],
                           tokenId=token["tokenId"], expiresAt=expiry)
        wait_for("renewed scoped account", lambda: graphql(API, credentials["token"], "{ me { userId } }"))
        save(path, credentials)
        if previous.get("tokenId"):
            request(endpoint + "/" + previous["tokenId"], pat, method="DELETE")
        print("Scoped test credentials ready", flush=True)


def main():
    os.umask(0o077)
    if os.environ.get("THOTH_DISPOSABLE") != "1":
        raise RuntimeError("THOTH_DISPOSABLE=1 is required")
    STATE.mkdir(parents=True, exist_ok=True, mode=0o700)
    CLIENT.mkdir(parents=True, exist_ok=True, mode=0o700)
    if sys.argv[1:] == ["--refresh"]:
        refresh_credentials()
        return 0
    if sys.argv[1:]:
        raise RuntimeError("Expected no arguments or --refresh")
    # A marker from the previous process is not evidence that this process is ready.
    legacy = (STATE / "ready").exists() and not (STATE / "bootstrap.json").exists()
    if legacy:
        raise RuntimeError("Legacy bootstrap: run down --apply, then prepare --dataset PATH --apply")
    (STATE / "ready").unlink(missing_ok=True)
    wait_for("Zitadel readiness", lambda: request(ZITADEL + "/debug/ready"))
    identity = bootstrap()
    os.environ["PRIVATE_KEY"] = identity["privateKey"]
    def database_ready():
        with socket.create_connection(("thoth-db", 5432), timeout=3):
            return True
    wait_for("Thoth PostgreSQL", database_ready)
    print("Starting Thoth migrations and API", flush=True)
    child = subprocess.Popen(["thoth", "init"])
    def stop(signum, frame):
        child.terminate()
    signal.signal(signal.SIGTERM, stop)
    signal.signal(signal.SIGINT, stop)
    try:
        pat = (STATE / "admin.pat").read_text().strip()
        wait_for("authenticated Thoth API", lambda: graphql(API, pat, "{ me { userId } }"))
        refresh_credentials()
        (STATE / "ready").touch(mode=0o600)
        print("READY: Thoth with scoped account, publisher and imprint", flush=True)
        return child.wait()
    finally:
        (STATE / "ready").unlink(missing_ok=True)
        if child.poll() is None:
            child.terminate()
            try:
                child.wait(timeout=10)
            except subprocess.TimeoutExpired:
                child.kill()
                child.wait()


if __name__ == "__main__":
    try:
        sys.exit(main())
    except Exception as error:
        print(f"Environment failed: {type(error).__name__}: {error}", file=sys.stderr)
        sys.exit(1)
