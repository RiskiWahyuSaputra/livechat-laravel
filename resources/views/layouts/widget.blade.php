<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat Support</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: transparent !important;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        [x-cloak] { display: none !important; }
        
        /* Hide FAB in the widget view because it will be rendered by the host script */
        .chat-widget-container > button {
            display: none !important;
        }
        
        /* Ensure the popup is always visible in this view */
        .chat-widget-container > div[x-show="isOpen"] {
            display: flex !important;
            margin-bottom: 0 !important;
            position: fixed !important;
            bottom: 0 !important;
            right: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            max-height: 100vh !important;
            border-radius: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }
    </style>
</head>
<body>
    @yield('content')
    @stack('scripts')
</body>
</html>
