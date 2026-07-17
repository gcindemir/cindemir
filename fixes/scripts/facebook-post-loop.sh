#!/usr/bin/env bash
# Run paced Facebook batches until done or first hard failure.
set -u
export DISPLAY=:1
export PATH="$HOME/.local/bin:$PATH"
export FB_BATCH="${FB_BATCH:-15}"
export FB_DELAY="${FB_DELAY:-16}"
export FB_MODE="${FB_MODE:-repost}"
LOGDIR=/workspace/fixes
ROUND=0
MAX_ROUNDS="${MAX_ROUNDS:-20}"

while (( ROUND < MAX_ROUNDS )); do
  ROUND=$((ROUND + 1))
  remaining=$(python3 - <<'PY'
import json
from pathlib import Path
p = Path("/workspace/fixes/facebook-post-progress.json")
arts = json.loads(Path("/workspace/fixes/facebook-en-articles-enriched.json").read_text())
posted = set()
if p.exists():
    posted = {x.rstrip("/") for x in json.loads(p.read_text()).get("posted_quality_links", [])}
pending = [a for a in arts if a["link"].rstrip("/") not in posted]
print(len(pending))
PY
)
  echo "=== ROUND $ROUND remaining=$remaining batch=$FB_BATCH ===" | tee -a "$LOGDIR/fb-post-loop.log"
  if [[ "$remaining" -le 0 ]]; then
    echo "ALL_DONE" | tee -a "$LOGDIR/fb-post-loop.log"
    exit 0
  fi
  python3 -u /workspace/fixes/scripts/facebook-post-en-articles.py 2>&1 | tee "$LOGDIR/fb-post-round-$ROUND.log"
  code=${PIPESTATUS[0]}
  echo "round=$ROUND exit=$code" | tee -a "$LOGDIR/fb-post-loop.log"
  if [[ "$code" -ne 0 ]]; then
    echo "STOP_ON_FAIL" | tee -a "$LOGDIR/fb-post-loop.log"
    exit "$code"
  fi
  sleep 8
done
echo "MAX_ROUNDS_REACHED" | tee -a "$LOGDIR/fb-post-loop.log"
exit 0
