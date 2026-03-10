#!/bin/bash
echo "========================================="
echo "SERVER DIAGNOSTIC REPORT"
echo "========================================="

echo ""
echo "=== 1. REVERB PROCESS STATUS ==="
ps aux | grep -v grep | grep reverb || echo "REVERB IS NOT RUNNING!"

echo ""
echo "=== 2. PORT 8080 STATUS ==="
ss -tlnp 2>/dev/null | grep 8080 || netstat -tlnp 2>/dev/null | grep 8080 || echo "Nothing listening on 8080"

echo ""
echo "=== 3. .ENV BROADCAST/REVERB SETTINGS ==="
grep -E '^(BROADCAST|REVERB|PUSHER|VITE_REVERB)' /var/www/chat-mybrilian-com/.env

echo ""
echo "=== 4. NGINX CONFIG FILES ==="
find /etc/nginx -name "*mybrilian*" -o -name "*.conf" 2>/dev/null | head -20

echo ""
echo "=== 5. NGINX DEFAULT/SITE CONFIG ==="
for f in /etc/nginx/sites-enabled/* /etc/nginx/conf.d/*.conf; do
    if [ -f "$f" ]; then
        echo "--- FILE: $f ---"
        cat "$f"
        echo ""
    fi
done

echo ""
echo "=== 6. LATEST LARAVEL LOG ERRORS (last 50 lines with ERROR) ==="
grep 'ERROR' /var/www/chat-mybrilian-com/storage/logs/laravel-2026-03-09.log 2>/dev/null | tail -5

echo ""
echo "=== 7. LARAVEL BROADCASTING CONFIG ==="
cat /var/www/chat-mybrilian-com/config/broadcasting.php 2>/dev/null

echo ""
echo "=== 8. REVERB CONFIG ==="
cat /var/www/chat-mybrilian-com/config/reverb.php 2>/dev/null

echo ""
echo "========================================="
echo "DIAGNOSTIC COMPLETE"
echo "========================================="
