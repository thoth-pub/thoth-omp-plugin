#!/usr/bin/env python3
"""Manage only the Docker Compose project belonging to this test directory."""
import argparse
import fcntl
import hashlib
import json
import os
import re
import shutil
import signal
import tempfile
from pathlib import Path
import secrets
import subprocess
import sys

ROOT = Path(__file__).resolve().parent
STATE = ROOT / ".state"
PROJECT = "thoth-e2e-" + hashlib.sha256(str(ROOT).encode()).hexdigest()[:10]


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("command", choices=["prepare", "open", "run", "status", "down"])
    parser.add_argument("--apply", action="store_true", help="Execute changes; otherwise show the plan")
    parser.add_argument("--port", type=int, default=18000, help="Loopback port for a new environment")
    parser.add_argument("--dataset", type=Path, help="OMP stable-3_5_0 MySQL dataset (database.sql, files/, public/)")
    parser.add_argument("--spec", help="Run one filename from cypress/tests/functional (run only)")
    args = parser.parse_args()
    if args.spec and args.command != "run":
        parser.error("--spec is only supported by run")
    if args.spec and (Path(args.spec).name != args.spec or not args.spec.endswith(".cy.js")
                      or not (ROOT.parent.parent / "cypress/tests/functional" / args.spec).is_file()):
        parser.error("--spec must name an existing .cy.js file in cypress/tests/functional")
    if args.command == "prepare":
        if not args.dataset or not (args.dataset / "database.sql").is_file() or not all(
                (args.dataset / name).is_dir() for name in ("files", "public")):
            parser.error("prepare requires --dataset with database.sql, files/ and public/")
    elif args.dataset:
        parser.error("--dataset is only supported by prepare")
    if not 1024 <= args.port <= 65535:
        parser.error("--port must be between 1024 and 65535")
    if args.command != "status" and not args.apply:
        print(f"Plan: {args.command}; Docker project={PROJECT}; state={STATE}")
        if args.command == "prepare":
            print("Start/resume Thoth and refresh its scoped credentials before preparing OMP.")
            print(f"Restore {args.dataset.resolve()} into disposable omp-db/thoth_cypress only; context publicknowledge.")
            print("No OMP host checkout or database will be changed.")
        if args.command in ("open", "run"):
            print("Check authenticated OMP/Thoth connectivity, then launch Cypress without restoring data.")
        print("Use --apply to execute. Only this project's resources will be changed.")
        return
    if STATE.is_symlink():
        raise RuntimeError("Refusing a symlink as the state directory")
    STATE.mkdir(mode=0o700, exist_ok=True)
    os.chmod(STATE, 0o700)
    with open(STATE / "lock", "w") as lock:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        config_path = STATE / "runtime.json"
        config = json.loads(config_path.read_text()) if config_path.exists() else {}
        if args.command == "prepare" and not config:
            config = {"ZITADEL_MASTERKEY": secrets.token_hex(16), "THOTH_PORT": str(args.port),
                      # Administrative PAT is confined to the private disposable volume.
                      "BOOTSTRAP_PAT_EXPIRES": "2099-01-01T00:00:00Z", "version": 2}
        if args.command in ("prepare", "open", "run") and config.get("version") != 2:
            raise RuntimeError("Old or missing environment state: use down --apply, then prepare --dataset PATH --apply")
        if args.command == "prepare":
            config["OMP_DATASET"] = str(args.dataset.resolve())
            write_interactive_config(config)
            with open(config_path, "w", opener=lambda p, flags: os.open(p, flags, 0o600)) as target:
                json.dump(config, target)
        if args.command in ("open", "run") and not (STATE / "interactive.json").is_file():
            raise RuntimeError("Run prepare --dataset PATH --apply first")
        env = dict(os.environ, **{key: str(value) for key, value in config.items()})
        env.setdefault("ZITADEL_MASTERKEY", "unused-for-inspection-or-removal!")
        env.setdefault("BOOTSTRAP_PAT_EXPIRES", "2099-01-01T00:00:00Z")
        env.setdefault("OMP_DATASET", str(ROOT))  # Compose interpolation only for status/down.
        compose = ["docker", "compose", "-p", PROJECT, "-f", str(ROOT / "compose.json"),
                   "-f", str(ROOT / "compose.cypress.json")]
        if (STATE / "interactive.json").is_file():
            compose += ["-f", str(STATE / "interactive.json")]

        def run(*arguments, capture=False):
            return subprocess.run(compose + list(arguments), env=env, check=True,
                                  text=True, stdout=subprocess.PIPE if capture else None)

        if args.command == "down":
            run("down", "--volumes", "--remove-orphans")
            for name in ("client.json", "runtime.json", "interactive.json"):
                (STATE / name).unlink(missing_ok=True)
            print("Disposable containers, network, volumes and credentials removed; images retained.")
        elif args.command == "status":
            run("ps", "--all")
        elif args.command == "prepare":
            run("build", "api", "zitadel", "cypress")
            # Stop OMP before credentials rotate or its dataset is restored.
            run("stop", "cypress")
            run("up", "-d", "--wait", "--wait-timeout", "300", "gateway", "omp-db")
            run("exec", "-T", "api", "python3", "/environment/server.py", "--refresh")
            run("up", "-d", "--no-deps", "--force-recreate", "--wait", "--wait-timeout", "180", "cypress")
            check_connection(run)
            print("OMP and Thoth prepared. Use open --apply or run [--spec NAME.cy.js] --apply.")
        else:
            container = run("ps", "--status", "running", "-q", "cypress", capture=True).stdout.strip()
            if not container:
                raise RuntimeError("Run prepare --dataset PATH --apply first")
            check_connection(run)
            entrypoint = "plugins/generic/thoth/tests/environment/cypress-entrypoint.sh"
            if args.command == "run":
                execute_cypress(run, entrypoint, "run", *([args.spec] if args.spec else []))
            else:
                open_cypress(run, container, config, entrypoint)


def check_connection(run):
    try:
        run("exec", "-T", "cypress", "curl", "-fsS", "-o", "/dev/null",
            "http://127.0.0.1:8001/index.php/publicknowledge/en/login")
        run("exec", "-T", "cypress", "php", "plugins/generic/thoth/tests/environment/cypress-check.php")
    except subprocess.CalledProcessError:
        raise RuntimeError("OMP/Thoth authentication or connectivity failed. "
                           "Run prepare --dataset PATH --apply before launching Cypress.") from None


def write_interactive_config(config):
    service = {
        "command": ["prepare"],
        "volumes": [{"type": "bind", "source": str(ROOT.parent.parent / "cypress"),
                     "target": "/var/www/omp/plugins/generic/thoth/cypress", "read_only": True}],
        "healthcheck": {"test": ["CMD", "curl", "-fsS", "http://127.0.0.1:8001/index.php/publicknowledge/en/login"],
                        "interval": "2s", "timeout": "3s", "retries": 60, "start_period": "10s"},
    }
    display = os.environ.get("DISPLAY", "")
    config.pop("OMP_X11_SOCKET", None)
    if re.fullmatch(r":\d+(?:\.\d+)?", display):
        socket = Path("/tmp/.X11-unix") / ("X" + display[1:].split(".")[0])
        if socket.is_socket():
            config["OMP_X11_SOCKET"] = str(socket)
            service["volumes"].append({"type": "bind", "source": str(socket),
                                       "target": str(socket), "read_only": True})
    (STATE / "interactive.json").write_text(json.dumps({"services": {"cypress": service}}))


def execute_cypress(run, entrypoint, *arguments, display=None, authority=None):
    # Docker exec does not forward Ctrl+C reliably. Own a separate process group
    # and stop it explicitly before releasing the CLI lock or removing X authority.
    pid_file = "/tmp/thoth-cypress-" + secrets.token_hex(8) + ".pid"
    # Electron's separate GPU process fails X11 authorization under XWayland.
    # Cypress 14.5.4 forwards this single Chromium switch before its CLI arguments;
    # keep graphics in the authorized process only for the disposable GUI session.
    environment = ["-e", "DISPLAY=" + display, "-e", "XAUTHORITY=" + authority,
                   "-e", "CYPRESS_INTERNAL_DEV_DEBUG=--in-process-gpu"] if display else []
    try:
        run("exec", "-T", *environment, "cypress", "setsid", "--wait", "bash", "-c",
            'echo $$ > "$1"; shift; exec "$@"', "thoth-cypress", pid_file,
            "bash", entrypoint, *arguments)
    finally:
        run("exec", "-T", "cypress", "python3", "-c", """
import os, pathlib, signal, sys, time
path = pathlib.Path(sys.argv[1])
if path.exists():
    pid = int(path.read_text())
    if pid <= 1:
        raise SystemExit('Invalid Cypress process group')
    try:
        os.killpg(pid, signal.SIGTERM)
        for attempt in range(20):
            time.sleep(0.1)
            os.killpg(pid, 0)
        os.killpg(pid, signal.SIGKILL)
    except ProcessLookupError:
        pass
    path.unlink()
""", pid_file)


def open_cypress(run, container, config, entrypoint):
    display = os.environ.get("DISPLAY", "")
    if not re.fullmatch(r":\d+(?:\.\d+)?", display) or not shutil.which("xauth"):
        raise RuntimeError("Interactive mode requires a local X11/XWayland DISPLAY and xauth")
    socket = "/tmp/.X11-unix/X" + display[1:].split(".")[0]
    if config.get("OMP_X11_SOCKET") != socket:
        raise RuntimeError("Run prepare again from this graphical session before open")
    # Copy only this display's cookie; never relax the X server's access control.
    result = subprocess.run(["xauth", "nlist", display], capture_output=True, check=True, text=True)
    records = result.stdout.splitlines()
    with tempfile.TemporaryDirectory(prefix="xauth-", dir=STATE) as temp:
        authority = Path(temp) / "authority"
        authority.touch(mode=0o600)
        if not records:
            # XWayland may authorize the host user without an existing cookie.
            # Request an expiring cookie instead of changing global X access rules.
            subprocess.run(["xauth", "-f", str(authority), "generate", display, ".",
                            "trusted", "timeout", "60"], capture_output=True, check=True)
            result = subprocess.run(["xauth", "-f", str(authority), "nlist", display],
                                    capture_output=True, check=True, text=True)
            records = result.stdout.splitlines()
            if not records:
                raise RuntimeError("Could not obtain temporary X11 authorization")
        subprocess.run(["xauth", "-f", str(authority), "nmerge", "-"],
                       input="\n".join("ffff" + record[4:] for record in records) + "\n",
                       text=True, capture_output=True, check=True)
        # Chromium GPU subprocesses do not inherit XAUTHORITY; use the container's
        # default location as well. Never touch the host's authority file.
        remote = "/root/.Xauthority"
        run("exec", "-T", "cypress", "test", "!", "-e", remote)
        try:
            subprocess.run(["docker", "cp", str(authority), container + ":" + remote], check=True)
            execute_cypress(run, entrypoint, "open", display=display, authority=remote)
        finally:
            run("exec", "-T", "cypress", "rm", "-f", remote)


if __name__ == "__main__":
    def interrupt(_signum, _frame):
        raise KeyboardInterrupt()

    signal.signal(signal.SIGTERM, interrupt)
    signal.signal(signal.SIGHUP, interrupt)
    try:
        main()
    except KeyboardInterrupt:
        print("Command interrupted; disposable resources remain available.", file=sys.stderr)
        sys.exit(130)
    except (OSError, RuntimeError, subprocess.CalledProcessError) as error:
        print(f"Environment command failed: {error}", file=sys.stderr)
        sys.exit(1)
