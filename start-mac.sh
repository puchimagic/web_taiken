#!/bin/bash
set -e

HOST="127.0.0.1"
PORT="8000"
DOCROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/public" && pwd)"

echo "PHP組み込みサーバーを起動します: http://${HOST}:${PORT}/index.php"
echo "停止するには Ctrl+C を押してください。"

php -d upload_max_filesize=200M -d post_max_size=200M -S "${HOST}:${PORT}" -t "${DOCROOT}"
