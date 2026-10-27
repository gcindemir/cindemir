#!/usr/bin/env bash
set -euo pipefail
# Persistent desktop Chrome profile (non-default path so CDP works)
PROFILE=/home/ubuntu/.chrome-agent
mkdir -p "$PROFILE"
python3 - <<'PY'
import os, signal, subprocess
me, parent = os.getpid(), os.getppid()
out = subprocess.check_output(['ps', '-eo', 'pid=,args='], text=True)
for line in out.splitlines():
    line = line.strip()
    if not line:
        continue
    pid_s, cmd = line.split(None, 1)
    pid = int(pid_s)
    if pid in (me, parent):
        continue
    if '/opt/google/chrome/chrome' in cmd or 'google-chrome' in cmd:
        if '.chrome-agent' in cmd or 'fb-jifkln9c' in cmd or 'remote-debugging-port=9222' in cmd:
            try:
                os.kill(pid, signal.SIGKILL)
                print('killed', pid)
            except Exception as e:
                print('skip', pid, e)
print('clean ok')
PY
sleep 1
export DISPLAY=:1
# Bypass /usr/local/bin wrapper (forces default profile where CDP is disabled)
nohup /opt/google/chrome/chrome \
  --no-sandbox \
  --test-type \
  --disable-dev-shm-usage \
  --use-gl=angle \
  --use-angle=swiftshader-webgl \
  --password-store=basic \
  --no-first-run \
  --no-default-browser-check \
  --user-data-dir="$PROFILE" \
  --class=google-chrome \
  --window-size=1400,900 \
  --window-position=50,50 \
  --remote-debugging-port=9222 \
  https://www.facebook.com/login \
  >/tmp/fb-chrome-launch.log 2>&1 &
echo "chrome_pid=$!"
sleep 5
head -20 /tmp/fb-chrome-launch.log || true
