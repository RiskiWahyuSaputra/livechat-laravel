import re
import sys

def main():
    try:
        with open('.env', 'r') as f:
            content = f.read()
        
        # Backend config (Local/Internal traffic)
        content = re.sub(r'(?m)^REVERB_HOST=.*', 'REVERB_HOST="127.0.0.1"', content)
        content = re.sub(r'(?m)^REVERB_PORT=.*', 'REVERB_PORT=8080', content)
        content = re.sub(r'(?m)^REVERB_SCHEME=.*', 'REVERB_SCHEME="http"', content)
        
        # Frontend config (Vite/External traffic)
        content = re.sub(r'(?m)^VITE_REVERB_HOST=.*', 'VITE_REVERB_HOST="chat.mybrilian.com"', content)
        content = re.sub(r'(?m)^VITE_REVERB_PORT=.*', 'VITE_REVERB_PORT=443', content)
        content = re.sub(r'(?m)^VITE_REVERB_SCHEME=.*', 'VITE_REVERB_SCHEME="https"', content)
        
        with open('.env', 'w') as f:
            f.write(content)
            
        print("Successfully updated .env file for Reverb decoupling.")
    except Exception as e:
        print(f"Error updating .env: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()
