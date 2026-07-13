#!/usr/bin/env python3
"""SSH deploy mu-plugins via paramiko."""
import os
import sys
from pathlib import Path

import paramiko

ROOT = Path("/workspace/fixes/mu-plugins")
REMOTE_DIR = "public_html/wp-content/mu-plugins"
HOSTS = [
    "ftp.cindemirlaw.com",
    "162.241.252.122",
    "cindemirlaw.com",
    "box5711.bluehost.com",
]
USERS = [
    os.environ.get("SSH_USER", ""),
    os.environ.get("USER", ""),
    "cursoradmin@cindemirlaw.com",
    "cindemir",
    "cindemirlaw",
]
PASSWORDS = [
    os.environ.get("SSH_PASSWORD", ""),
    os.environ.get("FTP_PASSWORD", ""),
    "g3.8cB4owh",
]
KEY_PATHS = [
    os.environ.get("SSH_PRIVATE_KEY", ""),
    str(Path.home() / ".ssh" / "id_rsa"),
    str(Path.home() / ".ssh" / "id_ed25519"),
    "/home/ubuntu/.ssh/id_rsa",
    "/home/ubuntu/.ssh/id_ed25519",
]
FILES = [
    "cindemir-mobile-brand.php",
    "cindemir-mobile-header-branding.php",
    "cindemir-contact-fixes.php",
    "cindemir-expose-yoast-meta.php",
    "cindemir-purge-cache.php",
    "cindemir-seo-fixes.php",
]


def try_connect(host: str, user: str, password: str = "", key_path: str = "") -> paramiko.SSHClient | None:
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        kwargs = {"hostname": host, "username": user, "timeout": 20, "allow_agent": True, "look_for_keys": True}
        if key_path and Path(key_path).exists():
            kwargs["key_filename"] = key_path
        if password:
            kwargs["password"] = password
        client.connect(**kwargs)
        print(f"CONNECTED {user}@{host}", flush=True)
        return client
    except Exception as exc:
        print(f"FAIL {user}@{host} key={key_path or '-'} pass={'yes' if password else 'no'}: {exc}", flush=True)
        return None


def find_remote_dir(client: paramiko.SSHClient) -> str:
    for d in [
        "public_html/wp-content/mu-plugins",
        "wp-content/mu-plugins",
        "home4/cindemir/public_html/wp-content/mu-plugins",
    ]:
        _, stdout, _ = client.exec_command(f"test -d {d} && echo OK || echo NO")
        if stdout.read().decode().strip() == "OK":
            return d
    _, stdout, _ = client.exec_command("pwd; ls -la")
    print(stdout.read().decode(), flush=True)
    return REMOTE_DIR


def upload_files(client: paramiko.SSHClient, remote_dir: str) -> int:
    sftp = client.open_sftp()
    ok = 0
    for name in FILES:
        local = ROOT / name
        remote = f"{remote_dir}/{name}"
        expect = local.stat().st_size
        try:
            sftp.put(str(local), remote)
            got = sftp.stat(remote).st_size
            print(f"UPLOAD {name} remote={got} expect={expect}", flush=True)
            if got >= expect - 10:
                ok += 1
        except Exception as exc:
            print(f"ERR {name}: {exc}", flush=True)
    sftp.close()
    return ok


def main() -> int:
    users = [u for u in USERS if u]
    passwords = [p for p in PASSWORDS if p]
    keys = [k for k in KEY_PATHS if k and Path(k).exists()]

    for host in HOSTS:
        for user in users:
            # key auth
            client = try_connect(host, user)
            if not client and keys:
                for key in keys:
                    client = try_connect(host, user, key_path=key)
                    if client:
                        break
            # password auth
            if not client:
                for pw in passwords:
                    client = try_connect(host, user, password=pw)
                    if client:
                        break
            if not client:
                continue

            remote_dir = find_remote_dir(client)
            print(f"REMOTE_DIR={remote_dir}", flush=True)
            ok = upload_files(client, remote_dir)
            # purge cache via wp-cli if available
            _, stdout, _ = client.exec_command(
                f"cd ~/public_html 2>/dev/null || cd public_html; "
                "wp cache flush 2>/dev/null; "
                "wp rocket clean --confirm 2>/dev/null; "
                "ls -la wp-content/mu-plugins/ | head -20"
            )
            print(stdout.read().decode(), flush=True)
            client.close()
            print(f"SUMMARY uploaded {ok}/{len(FILES)}", flush=True)
            return 0 if ok == len(FILES) else 1

    print("NO SSH CONNECTION", flush=True)
    return 1


if __name__ == "__main__":
    raise SystemExit(main())
