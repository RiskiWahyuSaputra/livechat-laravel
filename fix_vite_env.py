import os
import re

env_path = "/var/www/chat-mybrilian-com/.env"
with open(env_path, 'r') as f:
    content = f.read()

# Make sure VITE_REVERB variables are hardcoded correctly for the browser
content = re.sub(r'^VITE_REVERB_HOST=.*', 'VITE_REVERB_HOST="chat.mybrilian.com"', content, flags=re.MULTILINE)
content = re.sub(r'^VITE_REVERB_PORT=.*', 'VITE_REVERB_PORT=443', content, flags=re.MULTILINE)
content = re.sub(r'^VITE_REVERB_SCHEME=.*', 'VITE_REVERB_SCHEME="https"', content, flags=re.MULTILINE)

with open(env_path, 'w') as f:
    f.write(content)
print("Updated .env VITE variables successfully.")
