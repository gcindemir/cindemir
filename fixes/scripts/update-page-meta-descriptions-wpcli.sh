#!/usr/bin/env bash
# WP-CLI alternative for updating Yoast meta descriptions (run on Bluehost via SSH).
#
# Usage (from WordPress root, e.g. public_html):
#   wp eval-file /path/to/fixes/scripts/update-page-meta-descriptions-wpcli.php
#
# Or copy this file to the server and run:
#   wp eval-file update-page-meta-descriptions-wpcli.php
set -euo pipefail
echo "Run on server with WP-CLI: wp eval-file fixes/scripts/update-page-meta-descriptions-wpcli.php"
