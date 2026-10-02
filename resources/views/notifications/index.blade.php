<x-app-layout>
    <x-slot name="header">Notifikasi</x-slot>

    <div class="max-w-3xl mx-auto space-y-4" x-data="{
        csrfToken: document.querySelector('meta[name=csrf-token]').getAttribute('content'),
        async markAll() {
            try {
                const res = await fetch('{{ route('notifications.read-all') }}', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken }
                });
                if (res.ok) window.location.reload();
            } catch (e) {}
        },
        async openItem(id, url) {
            try {
                await fetch('{{ url('/notifications') }}/' + id + '/read', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken }
                });
            } catch (e) {}
            finally { if (url) window.location.href = url; }
        }
    }">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700">Daftar notifikasi</h2>
            @if($notifications->total() > 0)
                <button @click="markAll()" type="button" class="text-xs font-medium text-primary-dark hover:underline">Tandai semua sudah dibaca</button>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden divide-y divide-gray-50">
            @forelse($notifications as $n)
                @php
                    $isUnread = is_null($n->read_at);
                    $data = $n->data;
                @endphp
                <button
                    type="button"
                    @click="openItem('{{ $n->id }}', '{{ $data['url'] ?? '' }}')"
                    class="w-full text-left px-4 py-3 hover:bg-primary-light/40 transition flex items-start gap-3 {{ $isUnread ? 'bg-primary-light/60' : 'bg-white' }}"
                >
                    <span class="mt-2 w-2 h-2 rounded-full flex-shrink-0 {{ $isUnread ? 'bg-primary' : 'bg-transparent' }}"></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-800 truncate">{{ $data['title'] ?? $n->type }}</span>
                            @if($isUnread)
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-primary text-white font-semibold">Belum dibaca</span>
                            @endif
                        </div>
                        @if(!empty($data['message']))
                            <div class="text-xs text-gray-500 line-clamp-2 mt-0.5">{{ $data['message'] }}</div>
                        @endif
                        <div class="text-[11px] text-gray-400 mt-1">{{ $n->created_at->diffForHumans() }}</div>
                    </div>
                </button>
            @empty
                <div class="px-4 py-10 text-center">
                    <div class="text-sm font-medium text-gray-600">Belum ada notifikasi</div>
                    <div class="text-xs text-gray-400 mt-1">Notifikasi Workspace, pengumuman, surat, dan ujian akan muncul di sini.</div>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="pt-2">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
