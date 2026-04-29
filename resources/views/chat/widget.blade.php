@extends('layouts.widget')

@section('content')
    <x-chat-widget :isAuthenticated="$isAuthenticated" />

    <script>
        // Override chatWidget BEFORE Alpine initializes so isOpen starts as true
        // and the close button sends a postMessage to parent window instead of closing locally
        document.addEventListener('alpine:init', () => {
            const originalChatWidget = Alpine.data('chatWidget');
            Alpine.data('chatWidget', () => {
                const data = originalChatWidget();
                data.isOpen = true; // Always open inside the iframe
                data.initWidget = async function() {
                    // Call original init logic but keep isOpen = true
                    await originalChatWidget().initWidget.call(this);
                    this.isOpen = true;
                };
                data.toggleChat = () => {
                    // Notify parent window to close the iframe container
                    window.parent.postMessage('close-chat', '*');
                };
                return data;
            });
        });
    </script>
@endsection

