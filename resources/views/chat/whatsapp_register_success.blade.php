<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Berhasil - BRILLIAN.BIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md text-center">
        <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Data Terkonfirmasi!</h2>
        <p class="text-gray-600 mb-8">Terima kasih. Anda sekarang dapat menutup halaman ini dan kembali ke WhatsApp untuk melanjutkan percakapan dengan Agent kami.</p>
        
        <a href="https://wa.me/{{ env('WHATSAPP_NUMBER') }}" class="inline-block bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-8 rounded-lg shadow-md transition-all">
            Kembali ke WhatsApp
        </a>
    </div>
</body>
</html>
