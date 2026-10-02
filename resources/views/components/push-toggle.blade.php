<div
    x-data="{
        status: 'loading',
        busy: false,
        supported: false,
        csrfToken: document.querySelector('meta[name=csrf-token]').getAttribute('content'),
        urls: {
            vapid: '{{ route('push.vapid') }}',
            subscribe: '{{ route('push.subscribe') }}',
            unsubscribe: '{{ route('push.unsubscribe') }}',
        },
        async init() {
            this.supported = 'Notification' in window && 'serviceWorker' in navigator && 'PushManager' in window;
            if (!this.supported) { this.status = 'unsupported'; return; }
            if (Notification.permission === 'denied') { this.status = 'denied'; return; }
            try {
                const reg = await navigator.serviceWorker.ready.catch(() => null);
                const sub = reg ? await reg.pushManager.getSubscription() : null;
                this.status = sub ? 'active' : 'inactive';
            } catch (e) { this.status = 'inactive'; }
        },
        async enable() {
            if (this.busy) return;
            this.busy = true;
            try {
                const perm = await Notification.requestPermission();
                if (perm !== 'granted') { this.status = perm === 'denied' ? 'denied' : 'inactive'; return; }
                const vapidRes = await fetch(this.urls.vapid, { headers: { 'Accept': 'application/json' } });
                if (!vapidRes.ok) throw new Error('vapid');
                const { key } = await vapidRes.json();
                if (!key) throw new Error('vapid empty');
                const reg = await navigator.serviceWorker.register('/sw.js');
                await navigator.serviceWorker.ready;
                const converted = this.urlBase64ToUint8Array(key);
                const sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: converted });
                const raw = sub.toJSON();
                const res = await fetch(this.urls.subscribe, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                    body: JSON.stringify({ endpoint: raw.endpoint, keys: raw.keys }),
                });
                if (!res.ok) throw new Error('subscribe failed');
                this.status = 'active';
            } catch (e) {
                this.status = 'error';
            } finally { this.busy = false; }
        },
        async disable() {
            if (this.busy) return;
            this.busy = true;
            try {
                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.getSubscription();
                if (sub) {
                    const endpoint = sub.endpoint;
                    await sub.unsubscribe();
                    await fetch(this.urls.unsubscribe, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                        body: JSON.stringify({ endpoint }),
                    });
                }
                this.status = 'inactive';
            } catch (e) { this.status = 'error'; }
            finally { this.busy = false; }
        },
        urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
            const raw = atob(base64);
            const out = new Uint8Array(raw.length);
            for (let i = 0; i < raw.length; ++i) out[i] = raw.charCodeAt(i);
            return out;
        }
    }"
    x-init="init()"
    class="inline-flex items-center gap-2"
>
    <template x-if="status === 'unsupported'">
        <span class="text-xs text-gray-400">Browser tidak mendukung notifikasi</span>
    </template>
    <template x-if="status === 'denied'">
        <span class="text-xs text-red-500">Izin notifikasi ditolak di browser</span>
    </template>
    <template x-if="status === 'inactive'">
        <button @click="enable()" :disabled="busy" type="button" class="text-xs font-medium px-3 py-1.5 rounded-lg bg-primary text-white hover:bg-primary-dark disabled:opacity-50">Aktifkan notifikasi browser</button>
    </template>
    <template x-if="status === 'active'">
        <button @click="disable()" :disabled="busy" type="button" class="text-xs font-medium px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-50">Nonaktifkan notifikasi browser</button>
    </template>
    <template x-if="status === 'loading'">
        <span class="text-xs text-gray-400">Memeriksa...</span>
    </template>
    <template x-if="status === 'error'">
        <button @click="enable()" type="button" class="text-xs text-red-500 hover:underline">Gagal — coba lagi</button>
    </template>
</div>
