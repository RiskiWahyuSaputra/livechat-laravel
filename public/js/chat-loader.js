// ─── Config Reader ────────────────────────────────────────────────────────────
// Reads window.BrillianChatConfig with type validation and fallback to defaults.
// Safe to call even if window.BrillianChatConfig is undefined, null, or non-object.
function getConfig() {
    var cfg = window.BrillianChatConfig;
    var defaults = {
        waNumber: '6283179191601',
        primaryColor: '#2563eb',
        position: 'bottom-right',
        hideWhatsapp: false,
        greetingText: null,
    };

    if (!cfg || typeof cfg !== 'object') return defaults;

    return {
        waNumber: (typeof cfg.waNumber === 'string' && /^\d{10,15}$/.test(cfg.waNumber))
            ? cfg.waNumber : defaults.waNumber,
        primaryColor: (typeof cfg.primaryColor === 'string' && /^(#[0-9A-Fa-f]{3,6}|[a-zA-Z]+)$/.test(cfg.primaryColor))
            ? cfg.primaryColor : defaults.primaryColor,
        position: cfg.position === 'bottom-left' ? 'bottom-left' : 'bottom-right',
        hideWhatsapp: cfg.hideWhatsapp === true,
        greetingText: (typeof cfg.greetingText === 'string' && cfg.greetingText.length > 0)
            ? cfg.greetingText.substring(0, 200) : null,
    };
}
// ──────────────────────────────────────────────────────────────────────────────

function initChatLoader() {
    // Get the script's own URL to determine the APP_URL
    const scripts = document.getElementsByTagName('script');
    let scriptSrc = '';
    for (let i = 0; i < scripts.length; i++) {
        if (scripts[i].src.includes('chat-loader.js')) {
            scriptSrc = scripts[i].src;
            break;
        }
    }
    const APP_URL = scriptSrc ? new URL(scriptSrc).origin : (window.location ? window.location.origin : '');

    // ─── Read Config ─────────────────────────────────────────────────────────────
    const config = getConfig();
    const WA_NUMBER = config.waNumber;
    const WA_URL    = 'https://wa.me/' + WA_NUMBER;
    // ────────────────────────────────────────────────────────────────────────────

    // Inject Styles
    const style = document.createElement('style');
    style.innerHTML = `
        /* ── Wrapper: only holds the blue chat FAB ── */
        #brillian-chat-wrapper {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 999999;  /* highest — always visible */
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        @media (min-width: 768px) {
            #brillian-chat-wrapper { bottom: 32px; right: 32px; }
        }

        /* ── WhatsApp FAB — sits BELOW iframe ── */
        #brillian-wa-fab {
            position: fixed;
            bottom: 104px;
            right: 24px;
            z-index: 999996;  /* lower than iframe — gets covered when chat opens */
            width: 64px;
            height: 64px;
            border-radius: 50% !important;
            border: none;
            background-color: #25D366;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: 0 25px 50px -12px rgba(37, 211, 102, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: brillian-wa-pulse 2s infinite;
        }
        @media (min-width: 768px) {
            #brillian-wa-fab { bottom: 112px; right: 32px; }
        }
        #brillian-wa-fab:hover { transform: scale(1.1); background-color: #128C7E; }
        #brillian-wa-fab svg { width: 30px; height: 30px; }
        @keyframes brillian-wa-pulse {
            0%   { box-shadow: 0 25px 50px -12px rgba(37, 211, 102, 0.4); }
            50%  { box-shadow: 0 25px 50px -12px rgba(37, 211, 102, 0.65); }
            100% { box-shadow: 0 25px 50px -12px rgba(37, 211, 102, 0.4); }
        }

        /* ── Blue Chat FAB ── */
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
        #brillian-chat-fab:hover { transform: scale(1.1); background-color: #1d4ed8; }
        #brillian-chat-fab:active { transform: scale(0.95); }
        #brillian-chat-fab svg { width: 28px; height: 28px; }
        .brillian-chat-pulse { animation: brillian-chat-pulse 2s infinite; }
        @keyframes brillian-chat-pulse {
            0%   { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4); }
            70%  { box-shadow: 0 0 0 15px rgba(37, 99, 235, 0); }
            100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
        }

        /* ── Iframe popup — z-index BETWEEN WA FAB and chat FAB ── */
        #brillian-chat-iframe-container {
            position: fixed;
            bottom: 104px;
            right: 24px;
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
            transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                        transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            transform: scale(0.85) translateY(20px);
            z-index: 999997;  /* above WA FAB (999996), below chat wrapper (999999) */
        }
        @media (min-width: 768px) {
            #brillian-chat-iframe-container { bottom: 112px; right: 32px; }
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
    `;
    document.head.appendChild(style);

    // ─── Iframe container ────────────────────────────────────────────────────────
    const iframeContainer = document.createElement('div');
    iframeContainer.id = 'brillian-chat-iframe-container';

    // Apply position (bottom-left vs bottom-right)
    if (config.position === 'bottom-left') {
        iframeContainer.style.right = '';
        iframeContainer.style.left  = '24px';
    }

    const iframe = document.createElement('iframe');
    iframe.id  = 'brillian-chat-iframe';
    let iframeSrc = APP_URL + '/chat-widget';
    if (config.greetingText) {
        iframeSrc += '?greeting=' + encodeURIComponent(config.greetingText);
    }
    iframe.src = iframeSrc;
    iframe.setAttribute('allow', 'camera; microphone; geolocation');
    iframeContainer.appendChild(iframe);
    document.body.appendChild(iframeContainer);

    // ─── Wrapper (holds both FABs) ───────────────────────────────────────────────
    const wrapper = document.createElement('div');
    wrapper.id = 'brillian-chat-wrapper';

    // Apply position to wrapper
    if (config.position === 'bottom-left') {
        wrapper.style.right = '';
        wrapper.style.left  = '24px';
    }

    // ─── WhatsApp FAB (separate element, lower z-index) ────────────────────────
    if (!config.hideWhatsapp) {
        const waFab = document.createElement('a');
        waFab.id        = 'brillian-wa-fab';
        waFab.href      = WA_URL;
        waFab.target    = '_blank';
        waFab.rel       = 'noopener noreferrer';
        waFab.title     = 'Chat via WhatsApp';
        waFab.setAttribute('aria-label', 'Chat via WhatsApp');
        waFab.innerHTML = `
        <svg viewBox="0 0 24 24" fill="currentColor" style="width:30px;height:30px">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
    `;
        // Apply position to WA FAB
        if (config.position === 'bottom-left') {
            waFab.style.right = '';
            waFab.style.left  = '24px';
        }
        document.body.appendChild(waFab);  // Direct to body — z-index from CSS applies independently
    }

    // ── Blue Chat FAB ─────────────────────────────────────────────────────────
    const chatFab = document.createElement('button');
    chatFab.id        = 'brillian-chat-fab';
    chatFab.className = 'brillian-chat-pulse';
    chatFab.setAttribute('aria-label', 'Buka Chat');

    // Apply primaryColor as inline style (overrides CSS default)
    chatFab.style.backgroundColor = config.primaryColor;
    chatFab.innerHTML = `
        <svg id="brillian-icon-open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
        <svg id="brillian-icon-close" style="display:none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
    `;

    // ── Toggle Logic ──────────────────────────────────────────────────────────
    let isOpen = false;
    function toggleChat() {
        isOpen = !isOpen;
        if (isOpen) {
            iframeContainer.style.display = 'block';
            setTimeout(() => iframeContainer.classList.add('open'), 10);
            chatFab.querySelector('#brillian-icon-open').style.display  = 'none';
            chatFab.querySelector('#brillian-icon-close').style.display = 'block';
            chatFab.classList.remove('brillian-chat-pulse');
        } else {
            iframeContainer.classList.remove('open');
            setTimeout(() => { iframeContainer.style.display = 'none'; }, 300);
            chatFab.querySelector('#brillian-icon-open').style.display  = 'block';
            chatFab.querySelector('#brillian-icon-close').style.display = 'none';
            chatFab.classList.add('brillian-chat-pulse');
        }
    }

    chatFab.addEventListener('click', toggleChat);

    // Listen for close message from iframe
    window.addEventListener('message', function(event) {
        if (event.data === 'close-chat' && isOpen) toggleChat();
    });

    // Assemble: only chat FAB in wrapper (WA FAB is appended to body separately)
    wrapper.appendChild(chatFab);
    document.body.appendChild(wrapper);
}

// ─── Export for testing ───────────────────────────────────────────────────────
// Expose functions so Jest tests (and other consumers) can import them.
if (typeof module !== 'undefined' && module.exports) {
    // CommonJS / Jest environment
    module.exports = { getConfig: getConfig, initChatLoader: initChatLoader };
} else {
    // Browser environment — attach to window for optional external access
    window.getConfig       = getConfig;
    window.initChatLoader  = initChatLoader;
}
// ──────────────────────────────────────────────────────────────────────────────

// Auto-initialize when loaded in a browser (not in a test environment)
if (typeof module === 'undefined') {
    initChatLoader();
}
