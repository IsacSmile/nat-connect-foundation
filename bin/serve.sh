#!/usr/bin/env bash
cd "$(dirname "$0")/.."
php -d upload_max_filesize=10M -d post_max_size=12M -d memory_limit=256M -S 127.0.0.1:8000 -t public_html
