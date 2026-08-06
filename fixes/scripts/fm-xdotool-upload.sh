#!/usr/bin/env bash
set -euo pipefail
export DISPLAY=:1
ZIP="/home/ubuntu/Downloads/mu-plugins.zip"
LOG="/workspace/fixes/fm-xdotool.log"
exec > >(tee -a "$LOG") 2>&1

echo "=== FM upload start $(date) ==="

pkill -f "google-chrome" 2>/dev/null || true
sleep 2

TMPPROF=/tmp/bh-fm-profile-$$
rm -rf "$TMPPROF"
mkdir -p "$TMPPROF"
cp -a /home/ubuntu/.config/google-chrome/Default "$TMPPROF/" 2>/dev/null || true
cp -a /home/ubuntu/.config/google-chrome/"Local State" "$TMPPROF/" 2>/dev/null || true

google-chrome-stable --no-sandbox --disable-dev-shm-usage \
  --user-data-dir="$TMPPROF" \
  --window-size=1500 --window-height=950 --window-position=80,50 \
  "https://my.bluehost.com/hosting/app/cindemirlaw.com/cpanel/filemanager" &
CHPID=$!
sleep 18

scrot /workspace/fixes/fm-direct-1.png
WIN=$(xdotool search --class google-chrome | tail -1)
echo "WIN=$WIN title=$(xdotool getwindowname "$WIN" 2>/dev/null || true)"
xdotool windowactivate "$WIN"
sleep 1

# Sol ağaçta wp-content
xdotool mousemove --window "$WIN" 180 430 click 1
sleep 2
scrot /workspace/fixes/fm-direct-2.png

# mu-plugins
xdotool mousemove --window "$WIN" 180 455 click 1
sleep 2
scrot /workspace/fixes/fm-direct-3.png

# Upload toolbar
xdotool mousemove --window "$WIN" 360 210 click 1
sleep 4
scrot /workspace/fixes/fm-direct-4-upload.png

# Dosya seçici: path yapıştır
xdotool key ctrl+l
sleep 0.5
xdotool type --delay 10 "$ZIP"
sleep 0.5
xdotool key Return
sleep 2
xdotool key Return
sleep 15
scrot /workspace/fixes/fm-direct-5-afterupload.png

# Ana pencereye dön, zip seç + extract
xdotool windowactivate "$WIN"
sleep 1
xdotool mousemove --window "$WIN" 700 380 click 1
sleep 1
xdotool mousemove --window "$WIN" 520 210 click 1
sleep 8
scrot /workspace/fixes/fm-direct-6-final.png

echo "=== done $(date) ==="
