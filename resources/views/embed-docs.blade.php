<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Embed Widget Documentation</title>
    <style>
        body { font-family: sans-serif; max-width: 900px; margin: 40px auto; padding: 0 20px; color: #333; }
        h1 { color: #2563eb; }
        h2 { margin-top: 2rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem; }
        pre { background: #f3f4f6; padding: 1rem; border-radius: 6px; overflow-x: auto; }
        code { font-family: monospace; font-size: 0.9em; }
        table { border-collapse: collapse; width: 100%; margin: 1rem 0; }
        th, td { border: 1px solid #d1d5db; padding: 8px 12px; text-align: left; }
        th { background: #f9fafb; }
        .note { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; margin: 1rem 0; border-radius: 4px; }
        .contact-info { background: #eff6ff; border-left: 4px solid #2563eb; padding: 12px 16px; margin: 1rem 0; border-radius: 4px; }
    </style>
</head>
<body>

<h1>Embed Widget Documentation</h1>
<p>Panduan lengkap untuk meng-embed widget live chat Brillian ke website Anda.</p>

<h2>1. Snippet Embed Minimal</h2>
<p>Tambahkan satu baris script tag berikut sebelum tag penutup <code>&lt;/body&gt;</code>:</p>
<pre><code id="minimal-snippet">&lt;script src="{{ url('/js/chat-loader.js') }}" defer&gt;&lt;/script&gt;</code></pre>

<h2>2. Snippet Embed Lengkap dengan Konfigurasi</h2>
<p>Definisikan objek <code>window.BrillianChatConfig</code> sebelum script tag untuk mengkustomisasi widget:</p>
<pre><code>&lt;script&gt;
window.BrillianChatConfig = {
    waNumber:     '6281234567890', // string, 10-15 digit, default: '6283179191601'
    primaryColor: '#2563eb',       // string, warna CSS hex atau nama, default: '#2563eb'
    position:     'bottom-right',  // string, 'bottom-right' atau 'bottom-left', default: 'bottom-right'
    hideWhatsapp: false,           // boolean, sembunyikan tombol WhatsApp, default: false
    greetingText: 'Halo!',         // string, maks 200 karakter, default: null
};
&lt;/script&gt;
&lt;script src="{{ url('/js/chat-loader.js') }}" defer&gt;&lt;/script&gt;</code></pre>

<h3>Referensi Properti Konfigurasi</h3>
<table>
    <thead>
        <tr>
            <th>Properti</th>
            <th>Tipe Data</th>
            <th>Nilai Default</th>
            <th>Deskripsi</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>waNumber</code></td>
            <td>string</td>
            <td><code>'6283179191601'</code></td>
            <td>Nomor WhatsApp tujuan (10–15 digit, tanpa tanda + atau spasi)</td>
        </tr>
        <tr>
            <td><code>primaryColor</code></td>
            <td>string</td>
            <td><code>'#2563eb'</code></td>
            <td>Warna utama widget (format hex <code>#RRGGBB</code>, <code>#RGB</code>, atau nama warna CSS)</td>
        </tr>
        <tr>
            <td><code>position</code></td>
            <td>string</td>
            <td><code>'bottom-right'</code></td>
            <td>Posisi widget: <code>'bottom-right'</code> (kanan bawah) atau <code>'bottom-left'</code> (kiri bawah)</td>
        </tr>
        <tr>
            <td><code>hideWhatsapp</code></td>
            <td>boolean</td>
            <td><code>false</code></td>
            <td>Jika <code>true</code>, tombol WhatsApp FAB tidak ditampilkan</td>
        </tr>
        <tr>
            <td><code>greetingText</code></td>
            <td>string</td>
            <td><code>null</code></td>
            <td>Teks sambutan yang ditampilkan saat widget dibuka (maks 200 karakter)</td>
        </tr>
    </tbody>
</table>

<h2>3. Contoh Konfigurasi Siap Pakai</h2>

<h3>(a) Kustomisasi Warna Primer</h3>
<pre><code>&lt;script&gt;
window.BrillianChatConfig = {
    primaryColor: '#16a34a', // hijau
};
&lt;/script&gt;
&lt;script src="{{ url('/js/chat-loader.js') }}" defer&gt;&lt;/script&gt;</code></pre>

<h3>(b) Posisi Kiri Bawah</h3>
<pre><code>&lt;script&gt;
window.BrillianChatConfig = {
    position: 'bottom-left',
};
&lt;/script&gt;
&lt;script src="{{ url('/js/chat-loader.js') }}" defer&gt;&lt;/script&gt;</code></pre>

<h3>(c) Tanpa Tombol WhatsApp</h3>
<pre><code>&lt;script&gt;
window.BrillianChatConfig = {
    hideWhatsapp: true,
};
&lt;/script&gt;
&lt;script src="{{ url('/js/chat-loader.js') }}" defer&gt;&lt;/script&gt;</code></pre>

<h3>(d) Nomor WhatsApp Kustom</h3>
<pre><code>&lt;script&gt;
window.BrillianChatConfig = {
    waNumber: '6281234567890',
};
&lt;/script&gt;
&lt;script src="{{ url('/js/chat-loader.js') }}" defer&gt;&lt;/script&gt;</code></pre>

<h2>4. Catatan Teknis</h2>
<div class="note">
    <strong>⚠️ HTTPS Diperlukan:</strong> Website Anda <strong>harus menggunakan HTTPS</strong> agar cookie sesi (<code>guest_chat_token</code>) dapat dikirim di dalam iframe cross-domain. Tanpa HTTPS, sesi chat tidak akan tersimpan dengan benar di browser modern.
</div>

@if($whitelistActive)
<h2>5. Pendaftaran Domain</h2>
<div class="contact-info" id="contact-info">
    <strong>Domain Whitelist Aktif</strong>
    <p>Server ini menggunakan whitelist domain. Agar widget dapat berfungsi di website Anda, domain Anda perlu didaftarkan terlebih dahulu oleh administrator.</p>
    <p>Hubungi administrator untuk mendaftarkan domain Anda:</p>
    <ul>
        <li>📧 Email: <a href="mailto:admin@example.com">admin@example.com</a></li>
        <li>📞 Telepon: <a href="tel:+6281234567890">+62 812-3456-7890</a></li>
        <li>📝 Formulir: <a href="/contact">Formulir Kontak</a></li>
    </ul>
    <p>Sertakan informasi berikut saat menghubungi admin:</p>
    <ul>
        <li>Nama domain website Anda (contoh: <code>mywebsite.com</code>)</li>
        <li>Nama perusahaan / organisasi</li>
        <li>Tujuan penggunaan widget</li>
    </ul>
</div>
@endif

</body>
</html>
