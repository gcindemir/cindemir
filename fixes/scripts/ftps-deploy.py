#!/usr/bin/env python3
"""Upload mu-plugins to cindemirlaw.com via FTPS (Explicit TLS)."""
from __future__ import annotations

import os
import sys
import time
from ftplib import FTP_TLS, error_perm

HOST = os.environ.get("CINDEMIR_FTP_HOST", "162.241.252.122")
USER = os.environ.get("CINDEMIR_FTP_USER", "cursoradmin@cindemirlaw.com")
PASS = os.environ.get("CINDEMIR_FTP_PASS", "")
REMOTE_DIR = os.environ.get("CINDEMIR_FTP_PATH", "wp-content/mu-plugins")
ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "../.."))

UPLOADS = [
    ("fixes/mu-plugins/cindemir-contact-fixes.php", "cindemir-contact-fixes.php"),
]

REMOVE = [
    "cindemir-footer-live.php",
    "cindemir-deploy-footer-once.php",
    "zzz-deploy-test-917b.php",
]


def connect(max_attempts: int = 5) -> FTP_TLS:
    last_err: Exception | None = None
    for attempt in range(1, max_attempts + 1):
        try:
            ftp = FTP_TLS()
            ftp.connect(HOST, 21, timeout=90)
            ftp.login(USER, PASS)
            ftp.prot_p()
            ftp.set_pasv(True)
            return ftp
        except Exception as exc:  # noqa: BLE001
            last_err = exc
            print(f"connect attempt {attempt}/{max_attempts} failed: {exc}", file=sys.stderr)
            time.sleep(min(2 ** attempt, 16))
    raise RuntimeError(f"FTP connect failed after {max_attempts} attempts: {last_err}")


def ensure_dir(ftp: FTP_TLS, path: str) -> None:
    parts = [p for p in path.split("/") if p]
    for i in range(len(parts)):
        sub = "/".join(parts[: i + 1])
        try:
            ftp.cwd(sub)
        except error_perm:
            ftp.mkd(sub)
            ftp.cwd(sub)
    ftp.cwd("/")


def upload_file(ftp: FTP_TLS, local_path: str, remote_name: str) -> None:
    size = os.path.getsize(local_path)
    print(f"Uploading {remote_name} ({size} bytes) …")
    with open(local_path, "rb") as handle:
        ftp.storbinary(f"STOR {REMOTE_DIR}/{remote_name}", handle)
    print(f"  OK: {remote_name}")


def delete_remote(ftp: FTP_TLS, name: str) -> None:
    try:
        ftp.delete(f"{REMOTE_DIR}/{name}")
        print(f"Deleted {name}")
    except error_perm as exc:
        print(f"Skip delete {name}: {exc}")


def main() -> int:
    if not PASS:
        print("Set CINDEMIR_FTP_PASS", file=sys.stderr)
        return 1

    ftp = connect()
    try:
        ensure_dir(ftp, REMOTE_DIR)
        for rel, remote in UPLOADS:
            local = os.path.join(ROOT, rel)
            if not os.path.isfile(local):
                raise FileNotFoundError(local)
            for attempt in range(1, 6):
                try:
                    upload_file(ftp, local, remote)
                    break
                except Exception as exc:  # noqa: BLE001
                    print(f"  retry {attempt}/5: {exc}", file=sys.stderr)
                    ftp = connect()
                    time.sleep(min(2 ** attempt, 16))
            else:
                raise RuntimeError(f"Upload failed: {remote}")

        for name in REMOVE:
            delete_remote(ftp, name)

        print("Deploy complete.")
        return 0
    finally:
        try:
            ftp.quit()
        except Exception:  # noqa: BLE001
            pass


if __name__ == "__main__":
    raise SystemExit(main())
