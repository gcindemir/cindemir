#!/usr/bin/env bash
UA='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
while true; do
  e=$(curl -sI -A "$UA" https://cindemirlaw.com/wp-content/mu-plugins/cindemir-expose-yoast-meta.php | head -1)
  s=$(curl -sI -A "$UA" https://cindemirlaw.com/wp-content/mu-plugins/cindemir-seo-fixes.php | head -1)
  d=$(curl -s -A "$UA" https://cindemirlaw.com/about-us/ | grep -oP '(?<=meta name="description" content=")[^"]*' | head -1)
  echo "$(date -u +%H:%M:%S) expose=$e | seo=$s | about-us_meta_len=${#d}"
  sleep 15
done
