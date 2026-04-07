@extends('layouts.widget')

@section('content')
    <div x-data="{ isOpen: true }">
        <x-chat-widget :isAuthenticated="$isAuthenticated" />
    </div>

    <script>
        // Override chatWidget data to always be open in this view
        document.addEventListener('alpine:init', () => {
            const originalChatWidget = Alpine.data('chatWidget');
            Alpine.data('chatWidget', () => {
                const data = originalChatWidget();
                data.isOpen = true; // Force open
                data.toggleChat = () => {
                    // Send message to parent window to close the iframe
                    window.parent.postMessage('close-chat', '*');
                };
                return data;
            });
        });
    </script>
@endsection
