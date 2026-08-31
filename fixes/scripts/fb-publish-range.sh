#!/usr/bin/env bash
set -euo pipefail
cd /workspace
DAYS="${1:-43 44 45 46}"
pkill -9 -f 'user-data-dir=/home/ubuntu/.chrome-agent' 2>/dev/null || true
sleep 2
rm -f /home/ubuntu/.chrome-agent/Singleton* 2>/dev/null || true

for day in $DAYS; do
  echo "===== DAY $day $(date -u +%H:%M:%S) ====="
  python3 -u fixes/scripts/fb-publish-day.py --day "$day" 2>&1 | tee "/tmp/fb-day${day}.log"
  echo "exit:${PIPESTATUS[0]}"
done

python3 <<'PY'
import json
from pathlib import Path
CAL = Path("fixes/facebook-content-calendar.json")
cal = json.loads(CAL.read_text())
pub = sum(1 for d in cal["days"] for p in d["posts"] if p.get("status") == "published")
sched = sum(1 for d in cal["days"] for p in d["posts"] if p.get("status") == "scheduled")
fail = sum(1 for d in cal["days"] for p in d["posts"] if p.get("status") == "failed")
cal["stats"]["published_posts"] = pub
cal["stats"]["scheduled_posts"] = sched
cal["stats"]["failed_posts"] = fail
cal["stats"]["date_range"] = f"{cal['days'][0]['date']} → {cal['days'][-1]['date']}"
CAL.write_text(json.dumps(cal, indent=2, ensure_ascii=False) + "\n")
print("TOTAL", pub, "scheduled", sched, "failed", fail)
for d in cal["days"]:
    if d["day"] >= 43:
        print(d["day"], d["date"], d["status"], [p.get("status") for p in d["posts"]])
PY
echo BATCH_DONE
