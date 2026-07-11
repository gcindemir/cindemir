#!/usr/bin/env python3
"""Upload a file to Bluehost public_html via cPanel UAPI."""
import sys
import json
import urllib.parse
import requests
import browser_cookie3

CPANEL_BASE = "https://box5711.bluehost.com:2083"
CPANEL_DIR = "/public_html"


def get_session_url():
    cj = browser_cookie3.chrome(domain_name="box5711.bluehost.com")
    cookies = {c.name: c.value for c in cj}
    cpsession = urllib.parse.unquote(cookies.get("cpsession", ""))
    if not cpsession:
        raise RuntimeError("No cpsession cookie found")
    # cpsession format: user:security_token,session_token
    session_token = cpsession.split(",")[-1] if "," in cpsession else cpsession.split(":")[-1]
    return f"{CPANEL_BASE}/cpsess{session_token}", cookies


def upload_file(local_path: str, remote_name: str):
    base, cookies = get_session_url()
    url = f"{base}/execute/Fileman/upload_files"
    with open(local_path, "rb") as f:
        files = {"file-1": (remote_name, f, "application/octet-stream")}
        data = {
            "dir": CPANEL_DIR,
            "overwrite": "1",
        }
        r = requests.post(url, files=files, data=data, cookies=cookies, timeout=120)
    print("status:", r.status_code)
    try:
        print(json.dumps(r.json(), indent=2))
    except Exception:
        print(r.text[:2000])
    r.raise_for_status()


def delete_file(remote_name: str):
    base, cookies = get_session_url()
    src = f"{CPANEL_DIR}/{remote_name}"
    url = (
        f"{base}/json-api/cpanel"
        f"?cpanel_jsonapi_user=cindemir"
        f"&cpanel_jsonapi_apiversion=2"
        f"&cpanel_jsonapi_module=Fileman"
        f"&cpanel_jsonapi_func=fileop"
        f"&op=unlink"
        f"&sourcefiles={urllib.parse.quote(src)}"
    )
    r = requests.get(url, cookies=cookies, timeout=60)
    print("delete status:", r.status_code)
    print(r.text[:1000])


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: cpanel-upload.py upload <local> <remote> | delete <remote>")
        sys.exit(1)
    cmd = sys.argv[1]
    if cmd == "upload":
        upload_file(sys.argv[2], sys.argv[3])
    elif cmd == "delete":
        delete_file(sys.argv[2])
    else:
        print("Unknown command:", cmd)
        sys.exit(1)
