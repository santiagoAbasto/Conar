@unless((request()->attributes->get('analytics_bot')['type'] ?? null) === 'benign')
<script>
(() => {
    const endpoint = @json(route('telemetry.ingest'));
    const heartbeatMs = 30000;

    const uuid = () => {
        if (window.crypto?.randomUUID) return window.crypto.randomUUID();
        return "10000000-1000-4000-8000-100000000000".replace(/[018]/g, c =>
            (+c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> +c / 4).toString(16)
        );
    };

    const readOrCreate = (storage, key) => {
        let value = storage.getItem(key);
        if (!value) {
            value = uuid();
            storage.setItem(key, value);
        }
        return value;
    };

    const visitorId = readOrCreate(localStorage, "conar_visitor_id");
    const sessionId = readOrCreate(sessionStorage, "conar_session_id");

    const payload = (eventType) => JSON.stringify({
        visitor_id: visitorId,
        session_id: sessionId,
        event_type: eventType,
        url: window.location.href,
        path: window.location.pathname,
        title: document.title,
        referrer: document.referrer || null,
        metadata: {
            viewport: `${window.innerWidth}x${window.innerHeight}`,
            language: navigator.language || null,
        },
    });

    const send = (eventType, beacon = false) => {
        const body = payload(eventType);
        if (beacon && navigator.sendBeacon) {
            navigator.sendBeacon(endpoint, new Blob([body], { type: "application/json" }));
            return;
        }

        fetch(endpoint, {
            method: "POST",
            headers: { "Content-Type": "application/json", "Accept": "application/json" },
            body,
            keepalive: true,
            credentials: "same-origin",
        }).catch(() => {});
    };

    send("page_view");

    const heartbeat = window.setInterval(() => {
        if (document.visibilityState === "visible") send("heartbeat");
    }, heartbeatMs);

    window.addEventListener("pagehide", () => {
        window.clearInterval(heartbeat);
        send("session_end", true);
    });
})();
</script>
@endunless
