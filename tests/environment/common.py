"""HTTP helpers for the disposable Thoth instance (standard library only)."""
import json
import time
import urllib.error
import urllib.request


def request(url, token=None, data=None, method=None):
    headers = {"Content-Type": "application/json"}
    if token:
        headers["Authorization"] = "Bearer " + token
    payload = None if data is None else json.dumps(data).encode()
    req = urllib.request.Request(url, data=payload, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req, timeout=10) as response:
            body = response.read()
    except urllib.error.HTTPError as error:
        # Never include response bodies: identity-provider errors may contain secrets.
        raise RuntimeError(f"HTTP {error.code} from {req.get_method()} {req.host}{req.selector}") from None
    return json.loads(body) if body else {}


def wait_for(description, callback, timeout=120):
    deadline = time.monotonic() + timeout
    while True:
        try:
            result = callback()
            if result is not None and result is not False:
                return result
        except (OSError, ValueError, RuntimeError):
            pass
        if time.monotonic() >= deadline:
            raise RuntimeError(f"Timed out waiting for {description}")
        time.sleep(1)


def graphql(url, token, query, variables=None):
    result = request(url + "/graphql", token, {"query": query, "variables": variables or {}})
    if result.get("errors") or result.get("data") is None:
        raise RuntimeError("GraphQL operation failed (response omitted to protect credentials)")
    return result["data"]
