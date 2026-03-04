import Echo from "laravel-echo";

import Pusher from "pusher-js";
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
    enabledTransports: ["ws", "wss"],
    authEndpoint: window.broadcastingAuth || "/broadcasting/auth",
    auth: {
        headers: {
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content"),
        },
    },
});

window.Echo.connector.pusher.connection.bind("connected", () => {
    console.log("✅ Reverb Connected!");
});

window.Echo.connector.pusher.connection.bind("error", (err) => {
    console.error("❌ Reverb Connection Error:", err);
});

window.Echo.connector.pusher.connection.bind("disconnected", () => {
    console.warn("⚠️ Reverb Disconnected!");
});
