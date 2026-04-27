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
- `openclaw_public_base_url` (opsional, penting untuk kirim gambar/file dari server lokal)

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

Jika Laravel Anda berjalan di `127.0.0.1`, `localhost`, atau IP private lain, media WhatsApp tidak akan bisa di-fetch oleh gateway. Isi `openclaw_public_base_url` atau env `OPENCLAW_PUBLIC_BASE_URL` dengan URL publik aplikasi Anda, misalnya URL tunnel Cloudflare atau domain server.

Catatan penting:

- Fallback `local file` / `data:` URI tidak dipakai lagi untuk WhatsApp karena gateway akan menolaknya.
- Jika URL media masih lokal/private dan tidak ada base URL publik, Laravel akan tetap mengirim teksnya, tetapi medianya dilewati dan dicatat ke log.

Jika instance Anda butuh account tertentu, isi `openclaw_whatsapp_account`.

Jika Laravel berjalan dari proses Apache/PHP yang tidak mewarisi profile shell Anda, set juga environment berikut agar CLI memakai state OpenClaw yang sama:

- `OPENCLAW_STATE_DIR`
- `OPENCLAW_CONFIG_PATH`

## Outbound polling worker

Untuk gateway OpenClaw versi baru, mode yang paling stabil adalah membiarkan laptop gateway mengirim pesan keluar secara lokal melalui CLI resmi OpenClaw.

### Server Laravel

Set transport WhatsApp ke mode polling:

- `OPENCLAW_WHATSAPP_TRANSPORT=poll`

Laravel akan mengantrikan outbound WhatsApp ke cache/database server. Laptop gateway lalu mengambil antrean itu lewat HTTPS ke server.

### Laptop gateway

Laptop gateway tidak perlu menerima koneksi masuk tambahan. Cukup jalankan worker polling ini dari folder repo:

```bash
node scripts/openclaw-whatsapp-outbound-worker.mjs
```

Env minimal di laptop gateway:

- `APP_URL=https://domain-laravel-anda.com`
- `OPENCLAW_BRIDGE_TOKEN=<token yang sama dengan server>`
- `OPENCLAW_WHATSAPP_CHANNEL=whatsapp`
- `OPENCLAW_WHATSAPP_ACCOUNT` (opsional)

Worker akan polling ke endpoint server:

- `POST /api/openclaw/whatsapp/outbound/pull`
- `POST /api/openclaw/whatsapp/outbound/ack`

Env tambahan opsional:

- `OPENCLAW_OUTBOUND_POLL_INTERVAL_MS=2000`
- `OPENCLAW_OUTBOUND_LEASE_SECONDS=60`
- `OPENCLAW_OUTBOUND_WORKER_LOG`

Model ini cocok untuk server publik + laptop gateway di belakang NAT, karena semua koneksi outbound berangkat dari laptop gateway ke server.
