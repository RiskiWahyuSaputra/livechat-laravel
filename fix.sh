cd /var/www/chat-mybrilian-com
cat << 'EOF' > fix_env.py
import re
import sys

try:
    with open('.env', 'r') as f:
        content = f.read()

    content = re.sub(r'(?m)^REVERB_HOST=.*', 'REVERB_HOST="127.0.0.1"', content)
    content = re.sub(r'(?m)^REVERB_PORT=.*', 'REVERB_PORT=8080', content)
    content = re.sub(r'(?m)^REVERB_SCHEME=.*', 'REVERB_SCHEME="http"', content)
    
    content = re.sub(r'(?m)^VITE_REVERB_HOST=.*', 'VITE_REVERB_HOST="chat.mybrilian.com"', content)
    content = re.sub(r'(?m)^VITE_REVERB_PORT=.*', 'VITE_REVERB_PORT=443', content)
    content = re.sub(r'(?m)^VITE_REVERB_SCHEME=.*', 'VITE_REVERB_SCHEME="https"', content)

    with open('.env', 'w') as f:
        f.write(content)
        
    print("ENV successfully updated via Python script.")
except Exception as e:
    print(e)
    sys.exit(1)
EOF
python3 fix_env.py
php artisan config:clear
php artisan queue:restart
