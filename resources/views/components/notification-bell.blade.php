<div
    x-data="{
        open: false,
        count: 0,
        items: [],
        loading: false,
        csrfToken: document.querySelector('meta[name=csrf-token]').getAttribute('content'),
        urls: {
            list: '{{ route('notifications.index') }}',
            count: '{{ route('notifications.unread-count') }}',
            readAll: '{{ route('notifications.read-all') }}',
        },

        async fetchCount() {
            try {
                const res = await fetch(this.urls.count, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                this.count = data.count ?? 0;
            } catch (e) { /* abaikan: jangan rusak halaman */ }
        },

        async fetchList() {
            this.loading = true;
            try {
                const res = await fetch(this.urls.list, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                this.items = (data.data ?? []).slice(0, 10);
            } catch (e) { /* abaikan */ }
            finally { this.loading = false; }
        },

        toggle() {
            this.open = !this.open;
            if (this.open) this.fetchList();
        },

        markAllUrl() {
            return this.urls.readAll;
        },

        async markAll() {
            try {
                const res = await fetch(this.urls.readAll, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                });
                if (!res.ok) return;
                this.items = this.items.map(i => ({ ...i, read_at: i.read_at ?? new Date().toISOString() }));
                this.count = 0;
            } catch (e) { /* abaikan */ }
        },

        async openItem(item) {
            try {
                await fetch('{{ url('/notifications') }}/' + item.id + '/read', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                });
            } catch (e) { /* tetap redirect walau mark gagal */ }
            finally {
                if (item.url) window.location.href = item.url;
            }
        },

        timeAgo(value) {
            if (!value) return '';
            const diff = Math.floor((Date.now() - new Date(value).getTime()) / 1000);
            if (diff < 60) return 'baru saja';
            if (diff < 3600) return Math.floor(diff / 60) + ' mnt lalu';
            if (diff < 86400) return Math.floor(diff / 3600) + ' jam lalu';
            return Math.floor(diff / 86400) + ' hari lalu';
        }
    }"
    x-init="fetchCount()"
    class="relative"
    @click.outside="open = false"
>
    <button
        @click="toggle()"
        type="button"
        aria-label="Notifikasi"
        class="relative p-2 rounded-lg text-gray-500 hover:bg-primary-light hover:text-primary-dark transition"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        <span
            x-show="count > 0"
            x-text="count > 99 ? '99+' : count"
            x-cloak
            class="absolute -top-0.5 -right-0.5 min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-red-500 text-white text-[10px] font-semibold flex items-center justify-center"
        ></span>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden z-50"
    >
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <span class="text-sm font-semibold text-gray-800">Notifikasi</span>
            <button @click="markAll()" type="button" class="text-xs text-primary-dark hover:underline">Tandai semua sudah dibaca</button>
        </div>

        <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
            <template x-if="loading">
                <div class="px-4 py-6 text-center text-sm text-gray-400">Memuat...</div>
            </template>
            <template x-if="!loading && items.length === 0">
                <div class="px-4 py-6 text-center text-sm text-gray-400">Belum ada notifikasi</div>
            </template>
            <template x-for="item in items" :key="item.id">
                <button
                    @click="openItem(item)"
                    type="button"
                    class="w-full text-left px-4 py-3 hover:bg-primary-light/50 transition"
                    :class="!item.read_at ? 'bg-primary-light/60' : 'bg-white'"
                >
                    <div class="flex items-start gap-2">
                        <span x-show="!item.read_at" class="mt-1.5 w-2 h-2 rounded-full bg-primary flex-shrink-0"></span>
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-gray-800 truncate" x-text="item.title"></div>
                            <div class="text-xs text-gray-500 line-clamp-2" x-text="item.message"></div>
                            <div class="text-[11px] text-gray-400 mt-1 flex items-center gap-2">
                                <span x-text="timeAgo(item.created_at)"></span>
                                <span x-show="!item.read_at" class="text-primary-dark font-medium">Belum dibaca</span>
                            </div>
                        </div>
                    </div>
                </button>
            </template>
        </div>

        <a href="{{ route('notifications.index') }}" class="block text-center px-4 py-2.5 text-sm font-medium text-primary-dark hover:bg-primary-light/50 border-t border-gray-100">Lihat semua notifikasi</a>
    </div>
</div>
