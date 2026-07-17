#!/usr/bin/env bash
# Post up to 5 English articles per day with clickable link previews.
set -u
export DISPLAY=:1
export PATH="$HOME/.local/bin:$PATH"
export FB_MODE="${FB_MODE:-repost}"
export FB_BATCH="${FB_BATCH:-5}"
export FB_DAILY_LIMIT="${FB_DAILY_LIMIT:-5}"
export FB_DELAY="${FB_DELAY:-22}"
LOG="/workspace/fixes/fb-daily-$(date -u +%Y-%m-%d).log"
echo "=== FB daily run $(date -u +%Y-%m-%dT%H:%M:%SZ) limit=$FB_DAILY_LIMIT ===" | tee -a "$LOG"
python3 -u /workspace/fixes/scripts/facebook-post-en-articles.py 2>&1 | tee -a "$LOG"
code=${PIPESTATUS[0]}
echo "exit=$code $(date -u +%Y-%m-%dT%H:%M:%SZ)" | tee -a "$LOG"
exit "$code"
