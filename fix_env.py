import os
import re

env_path = "/var/www/chat-mybrilian-com/.env"
with open(env_path, 'r') as f:
    content = f.read()

content = re.sub(r'^REVERB_HOST=.*', 'REVERB_HOST="127.0.0.1"', content, flags=re.MULTILINE)
content = re.sub(r'^REVERB_PORT=.*', 'REVERB_PORT=8080', content, flags=re.MULTILINE)
content = re.sub(r'^REVERB_SCHEME=.*', 'REVERB_SCHEME=http', content, flags=re.MULTILINE)

with open(env_path, 'w') as f:
    f.write(content)
print("Updated .env successfully.")
