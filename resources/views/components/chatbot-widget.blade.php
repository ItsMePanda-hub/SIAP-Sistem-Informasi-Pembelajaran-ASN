<div
    x-data="{
        open: false,
        message: '',
        loading: false,
        messages: [],
        csrfToken: document.querySelector('meta[name=csrf-token]').getAttribute('content'),

        async send() {
            if (!this.message.trim() || this.loading) return;
            const userMsg = this.message.trim();
            this.messages.push({ role: 'user', text: userMsg });
            this.message = '';
            this.loading = true;

            try {
                const res = await fetch('{{ route('chatbot.ask') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify({ message: userMsg }),
                });

                const data = await res.json();

                if (res.status === 429) {
                    this.messages.push({ role: 'bot', text: 'Terlalu banyak pertanyaan. Coba lagi dalam 1 menit.' });
                } else {
                    this.messages.push({ role: 'bot', text: data.reply ?? 'Maaf, terjadi kesalahan.' });
                }
            } catch (e) {
                this.messages.push({ role: 'bot', text: 'Koneksi gagal. Periksa jaringan Anda.' });
            } finally {
                this.loading = false;
                this.$nextTick(() => {
                    const el = this.$refs.chatBody;
                    if (el) el.scrollTop = el.scrollHeight;
                });
            }
        },

        handleKey(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.send();
            }
        }
    }"
    class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-2"
    id="chatbot-widget"
>
    {{-- Chat panel --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        class="w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 flex flex-col overflow-hidden"
        style="max-height: 480px;"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 bg-teal-600 text-white">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold">SP</div>
                <div>
                    <div class="text-sm font-semibold leading-none">Asisten SIAP</div>
                    <div class="text-[10px] opacity-70">Tanyakan seputar SIAP</div>
                </div>
            </div>
            <button @click="open = false" class="opacity-70 hover:opacity-100 transition" aria-label="Tutup chat">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Messages --}}
        <div
            x-ref="chatBody"
            class="flex-1 overflow-y-auto px-4 py-3 space-y-3 bg-gray-50"
            style="min-height: 200px; max-height: 300px;"
        >
            <template x-if="messages.length === 0">
                <p class="text-xs text-gray-400 text-center pt-4">Halo! Ada yang bisa saya bantu seputar SIAP?</p>
            </template>

            <template x-for="(msg, i) in messages" :key="i">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div
                        :class="msg.role === 'user'
                            ? 'bg-teal-600 text-white text-xs rounded-xl rounded-tr-none px-3 py-2 max-w-[80%]'
                            : 'bg-white border border-gray-100 text-gray-700 text-xs rounded-xl rounded-tl-none px-3 py-2 max-w-[80%] shadow-sm'"
                        x-text="msg.text"
                    ></div>
                </div>
            </template>

            <template x-if="loading">
                <div class="flex justify-start">
                    <div class="bg-white border border-gray-100 text-gray-400 text-xs rounded-xl rounded-tl-none px-3 py-2 shadow-sm animate-pulse">
                        Mengetik…
                    </div>
                </div>
            </template>
        </div>

        {{-- Input --}}
        <div class="border-t border-gray-100 px-3 py-2 bg-white flex items-end gap-2">
            <textarea
                x-model="message"
                @keydown="handleKey($event)"
                placeholder="Ketik pertanyaan…"
                rows="1"
                class="flex-1 text-xs resize-none border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-teal-400 focus:border-teal-400 placeholder-gray-300"
                style="max-height: 72px;"
            ></textarea>
            <button
                @click="send()"
                :disabled="loading || !message.trim()"
                class="flex-shrink-0 w-8 h-8 rounded-full bg-teal-600 text-white flex items-center justify-center hover:bg-teal-700 disabled:opacity-40 transition"
                aria-label="Kirim"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- FAB Toggle button --}}
    <button
        @click="open = !open"
        id="chatbot-toggle-btn"
        class="w-13 h-13 rounded-full bg-teal-600 text-white shadow-lg hover:bg-teal-700 active:scale-95 transition-all duration-150 flex items-center justify-center"
        style="width: 52px; height: 52px;"
        aria-label="Buka asisten SIAP"
    >
        <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 16a2 2 0 01-2 2H7l-4 4V6a2 2 0 012-2h14a2 2 0 012 2v10z"/>
        </svg>
        <svg x-show="open" x-cloak xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
