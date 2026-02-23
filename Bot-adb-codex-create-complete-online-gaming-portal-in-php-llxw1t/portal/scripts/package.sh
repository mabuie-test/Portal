#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
mkdir -p var/dist
OUT="var/dist/crash_portal_package_$(date +%Y%m%d_%H%M%S).zip"
zip -r "$OUT" . -x "vendor/*" "logs/*" "storage/queue/*" "var/dist/*" "*.git/*"
echo "Pacote criado: $OUT"
