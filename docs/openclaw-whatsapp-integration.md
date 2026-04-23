# OpenClaw WhatsApp Integration

Project ini sekarang menyediakan endpoint Laravel untuk menerima pesan WhatsApp dari OpenClaw:

- `POST /api/webhook/openclaw/whatsapp`

## Tujuan

- Pesan dari WhatsApp masuk ke Laravel
- Laravel membuat atau melanjutkan `Conversation`
- Bot flow yang dipakai sama dengan chat web
- Balasan AI/admin dari Laravel dikirim kembali ke WhatsApp melalui OpenClaw CLI

## Setting Laravel

Isi pengaturan berikut di halaman admin:

- `openclaw_whatsapp_enabled`
- `openclaw_cli_path`
- `openclaw_whatsapp_channel`
- `openclaw_whatsapp_account` (opsional)
- `openclaw_bridge_token`

## Hook OpenClaw

Di sisi OpenClaw, arahkan hook event pesan WhatsApp masuk ke URL Laravel ini:

- `https://domain-anda.com/api/webhook/openclaw/whatsapp`

Sertakan salah satu autentikasi berikut:

- Header `Authorization: Bearer <openclaw_bridge_token>`
- atau header `X-OpenClaw-Bridge-Token: <openclaw_bridge_token>`

## Payload yang didukung

Endpoint Laravel ini cukup toleran dan mencoba membaca field umum seperti:

- `context.channelId`
- `context.from`
- `context.senderName`
- `context.bodyForAgent`
- `context.content`
- `context.messageId`
- `context.messageType`
- `context.mediaUrl`

Jika hook OpenClaw Anda memakai struktur `context`, endpoint ini akan lebih mudah cocok.

Catatan runtime:

- Gunakan `handler.js` untuk hook internal OpenClaw. Pada setup ini, `handler.ts` tidak akan dimuat otomatis oleh runtime gateway.
- Setelah mengubah file hook, jalankan ulang `openclaw hooks enable laravel-whatsapp-bridge` lalu restart `openclaw gateway run`.

## Catatan outbound

Outbound WhatsApp dari Laravel memakai command OpenClaw CLI:

- `openclaw message send --channel whatsapp --target <nomor> --message <pesan>`

Untuk media:

- `openclaw message send --channel whatsapp --target <nomor> --media <url>`

Jika instance Anda butuh account tertentu, isi `openclaw_whatsapp_account`.

Jika Laravel berjalan dari proses Apache/PHP yang tidak mewarisi profile shell Anda, set juga environment berikut agar CLI memakai state OpenClaw yang sama:

- `OPENCLAW_STATE_DIR`
- `OPENCLAW_CONFIG_PATH`
