(function() {
    // Get the script's own URL to determine the APP_URL
    const scripts = document.getElementsByTagName('script');
    let scriptSrc = '';
    for (let i = 0; i < scripts.length; i++) {
        if (scripts[i].src.includes('chat-loader.js')) {
            scriptSrc = scripts[i].src;
            break;
        }
    }
    const APP_URL = new URL(scriptSrc).origin;
    
    // Inject Styles
    const style = document.createElement('style');
    style.innerHTML = `
        #brillian-chat-wrapper {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 999999;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        #brillian-chat-fab {
            width: 64px;
            height: 64px;
            border-radius: 50% !important;
            background-color: #2563eb;
            color: white;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(37, 99, 235, 0.4);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        #brillian-chat-fab:hover {
            transform: scale(1.1);
            background-color: #1d4ed8;
        }
        #brillian-chat-fab:active {
            transform: scale(0.95);
        }
        #brillian-chat-fab svg {
            width: 28px;
            height: 28px;
        }
        #brillian-chat-iframe-container {
            position: absolute;
            bottom: 80px;
            right: 0;
            width: 380px;
            height: 600px;
            max-height: calc(100vh - 120px);
            max-width: calc(100vw - 48px);
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid #e2e8f0;
            overflow: hidden;
            display: none;
            transform-origin: bottom right;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            transform: scale(0.5) translateY(20px);
        }
        #brillian-chat-iframe-container.open {
            display: block;
            opacity: 1;
            transform: scale(1) translateY(0);
        }
        #brillian-chat-iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .fab-pulse-animation {
            animation: brillian-fab-pulse 2s infinite;
        }
        @keyframes brillian-fab-pulse {
            0% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(37, 99, 235, 0); }
            100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
        }
    `;
    document.head.appendChild(style);

    // Create Elements
    const wrapper = document.createElement('div');
    wrapper.id = 'brillian-chat-wrapper';

    const iframeContainer = document.createElement('div');
    iframeContainer.id = 'brillian-chat-iframe-container';
    
    const iframe = document.createElement('iframe');
    iframe.id = 'brillian-chat-iframe';
    iframe.src = APP_URL + '/chat-widget';
    iframe.setAttribute('allow', 'camera; microphone; geolocation');

    const fab = document.createElement('button');
    fab.id = 'brillian-chat-fab';
    fab.className = 'fab-pulse-animation';
    fab.innerHTML = `
        <svg id="brillian-icon-open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
        <svg id="brillian-icon-close" style="display:none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
    `;

    // Toggle Logic
    let isOpen = false;
    function toggleChat() {
        isOpen = !isOpen;
        if (isOpen) {
            iframeContainer.classList.add('open');
            fab.querySelector('#brillian-icon-open').style.display = 'none';
            fab.querySelector('#brillian-icon-close').style.display = 'block';
            fab.classList.remove('fab-pulse-animation');
        } else {
            iframeContainer.classList.remove('open');
            fab.querySelector('#brillian-icon-open').style.display = 'block';
            fab.querySelector('#brillian-icon-close').style.display = 'none';
            fab.classList.add('fab-pulse-animation');
        }
    }

    fab.addEventListener('click', toggleChat);

    // Listen for close message from iframe
    window.addEventListener('message', function(event) {
        if (event.data === 'close-chat') {
            toggleChat();
        }
    });

    // Assemble
    iframeContainer.appendChild(iframe);
    wrapper.appendChild(iframeContainer);
    wrapper.appendChild(fab);
    document.body.appendChild(wrapper);
})();
