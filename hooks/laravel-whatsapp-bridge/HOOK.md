---
name: laravel-whatsapp-bridge
description: "Forward inbound WhatsApp messages from OpenClaw to the Laravel webhook bridge"
metadata:
  { "openclaw": { "emoji": "📨", "events": ["message:received"], "requires": { "bins": ["node"] } } }
---

# Laravel WhatsApp Bridge

Forward inbound WhatsApp messages from OpenClaw to the Laravel endpoint at:

- `/api/webhook/openclaw/whatsapp`

This hook reads its configuration from the workspace `.env` file:

- `APP_URL`
- `OPENCLAW_BRIDGE_TOKEN`

Behavior:

- only forwards inbound WhatsApp messages
- ignores outbound/self messages
- posts a tolerant `context` payload that matches the Laravel controller

After adding this hook:

1. Run `openclaw hooks list`
2. Run `openclaw hooks enable laravel-whatsapp-bridge`
3. Restart `openclaw gateway run`

Catatan:

- Gunakan `handler.js` untuk runtime OpenClaw saat ini. `handler.ts` tidak dimuat otomatis oleh gateway.
