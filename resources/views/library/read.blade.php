<x-app-layout>
    <x-slot name="header">Baca Materi</x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div class="text-sm text-gray-500">
                <a href="{{ route('library.index') }}" class="text-primary hover:underline">SIAP Library</a>
                <span class="mx-1">/</span>
                <span class="text-gray-800 font-medium">Baca Materi</span>
            </div>
            <a href="{{ route('library.index') }}" class="px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50 bg-white">Kembali ke Library</a>
        </div>

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6 md:p-8 space-y-4">
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="px-2 py-1 rounded-full {{ $learningMaterial->scope->value === 'core' ? 'bg-teal-50 text-teal-700' : 'bg-amber-50 text-amber-700' }}">{{ $learningMaterial->scope->value === 'core' ? 'Core' : 'Custom' }}</span>
                <span class="px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ $learningMaterial->isLinkOnly() ? 'Link Only' : ucfirst($learningMaterial->material_type->value) }}</span>
                <span class="px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ $learningMaterial->level->name ?? '-' }}</span>
                <span class="px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ $learningMaterial->category->name ?? '-' }}</span>
                @if($learningMaterial->source?->license_type)
                    <span class="px-2 py-1 rounded-full bg-blue-50 text-blue-700">{{ $learningMaterial->source->license_type->value }}</span>
                @endif
            </div>

            <div>
                <h1 class="text-xl md:text-2xl font-semibold text-gray-900 leading-snug">{{ $learningMaterial->title }}</h1>
                @if($learningMaterial->description)
                    <p class="mt-2 text-sm text-gray-500 leading-relaxed">{{ $learningMaterial->description }}</p>
                @endif
            </div>

            @if($learningMaterial->source)
                <div class="text-xs text-gray-500 bg-gray-50 border border-gray-100 rounded-lg p-3 space-y-1">
                    <div class="font-semibold text-gray-700">Sumber &amp; Provenance</div>
                    <div>{{ $learningMaterial->source->source_name }} — {{ $learningMaterial->source->publisher }}</div>
                    @if($learningMaterial->source->document_identifier)
                        <div>Identitas Dokumen: {{ $learningMaterial->source->document_identifier }}</div>
                    @endif
                    @if($learningMaterial->source->version)
                        <div>Versi: {{ $learningMaterial->source->version }}</div>
                    @endif
                    @if($learningMaterial->source->license_type)
                        <div>Lisensi: <strong>{{ $learningMaterial->source->license_type->label() }}</strong></div>
                    @endif
                </div>
            @endif

            <div class="flex items-center justify-between text-xs text-gray-500 border border-gray-100 rounded-lg px-3 py-2 bg-gray-50">
                <span>Progress membaca: <strong id="progress-value">{{ $progress->progress_percent ?? 0 }}%</strong></span>
                <span id="progress-status" class="text-gray-400">
                    @if(($progress->progress_percent ?? 0) >= 100)
                        ✓ Selesai dibaca
                    @elseif(($progress->progress_percent ?? 0) > 0)
                        Sedang membaca
                    @else
                        Belum mulai
                    @endif
                </span>
            </div>

            <div class="flex items-center gap-2">
                @if($isBookmarked ?? false)
                    <form method="POST" action="{{ route('library.bookmark.destroy', $learningMaterial) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-1.5 border border-amber-200 bg-amber-50 text-amber-700 rounded-lg text-xs font-medium">★ Tersimpan</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('library.bookmark.store', $learningMaterial) }}">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-50">☆ Simpan</button>
                    </form>
                @endif
                <a href="{{ route('library.bookmarks') }}" class="text-xs text-primary hover:underline">Lihat Bookmark</a>
            </div>

            @if($learningMaterial->isNative())
                <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                    <div id="progress-bar" class="bg-primary h-1.5 rounded-full transition-all" style="width: {{ $progress->progress_percent ?? 0 }}%"></div>
                </div>
                <style>
                    #native-content{--native-lh:1.8}
                    #native-content p{margin:0.85em 0;line-height:1.8;overflow-wrap:break-word;hyphens:auto}
                    #native-content h2{font-size:1.17rem;line-height:1.35;font-weight:700;letter-spacing:-0.015em;color:#111827;margin:2em 0 0.65em;padding-bottom:0.35em;border-bottom:1px solid #f3f4f6}
                    #native-content h3{font-size:1rem;line-height:1.45;font-weight:600;color:#1f2937;margin:1.5em 0 0.55em}
                    #native-content ol,#native-content ul{margin:0.9em 0 0.9em 1.1em;line-height:1.75;text-align:left;text-indent:0}
                    #native-content ol{padding-left:1.1em}
                    #native-content ul{padding-left:1em}
                    #native-content li{margin:0.38em 0;padding-left:0.15em}
                    #native-content li::marker{color:#6b7280}
                    #native-content h2,#native-content h3,#native-content li{text-align:left;text-indent:0}
                    @media(min-width:768px){
                        #native-content p{text-align:justify;text-justify:inter-word;text-indent:1.5em}
                        #native-content h2+p,#native-content h3+p,#native-content ul+p,#native-content ol+p{ text-indent:0 }
                    }
                    @media(max-width:767px){
                        #native-content p{text-align:left;text-indent:0}
                    }
                </style>
                <article id="native-content" class="text-gray-800 text-[15px] md:text-base break-words">
                    @php $nativeBlocks = \App\Support\NativeContentRenderer::blocks((string) $learningMaterial->content); @endphp
                    @foreach($nativeBlocks as $block)
                        @if($block['type'] === 'heading' && $block['level'] === 2)
                            <h2>{{ $block['text'] }}</h2>
                        @elseif($block['type'] === 'heading')
                            <h3>{{ $block['text'] }}</h3>
                        @elseif($block['type'] === 'list' && $block['ordered'])
                            <ol class="list-decimal list-outside text-gray-800">
                                @foreach($block['items'] as $item)<li>{{ $item }}</li>@endforeach
                            </ol>
                        @elseif($block['type'] === 'list')
                            <ul class="list-disc list-outside text-gray-800">
                                @foreach($block['items'] as $item)<li>{{ $item }}</li>@endforeach
                            </ul>
                        @else
                            <p>{{ $block['text'] }}</p>
                        @endif
                    @endforeach
                </article>
            @endif

            @if($learningMaterial->isPdf())
                <div class="space-y-3">
                    <div class="text-sm text-gray-500">{{ $learningMaterial->file_original_name ?? 'Dokumen PDF' }}</div>
                    <div class="border border-gray-200 rounded-xl overflow-hidden bg-gray-50">
                        <iframe src="{{ route('library.pdf', $learningMaterial) }}" class="w-full h-[70vh] min-h-[480px]" title="PDF {{ $learningMaterial->title }}"></iframe>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('library.pdf', $learningMaterial) }}" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium">Buka PDF</a>
                        <a href="{{ route('library.index') }}" class="px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-600">Kembali</a>
                    </div>
                    <p class="text-xs text-gray-400">Jika PDF tidak tampil, gunakan tombol Buka PDF di atas (tetap melalui SIAP).</p>
                    <div class="pt-2">
                        @if(($progress->progress_percent ?? 0) >= 100)
                            <span class="inline-block px-4 py-2 bg-green-50 text-green-700 rounded-lg text-sm font-medium">✓ Selesai dibaca</span>
                        @else
                            <button id="pdf-complete-btn" type="button" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark">Tandai selesai membaca</button>
                        @endif
                    </div>
                </div>
            @endif

            @if($learningMaterial->isLinkOnly())
                <div class="space-y-4 p-5 border border-blue-100 rounded-xl bg-blue-50/30">
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-blue-100 text-blue-700 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </div>
                        <div class="space-y-1">
                            <div class="font-semibold text-gray-800 text-sm">Referensi Eksternal Resmi</div>
                            <p class="text-xs text-gray-600 leading-relaxed">
                                Materi ini disajikan sebagai referensi resmi yang dapat diakses langsung pada situs web penerbit. Tidak ada berkas file yang disalin ke sistem internal.
                            </p>
                        </div>
                    </div>

                    @if($learningMaterial->source?->source_url)
                        <div class="pt-2 flex flex-wrap gap-2 items-center">
                            <a href="{{ $learningMaterial->source->source_url }}" target="_blank" rel="noopener noreferrer" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark inline-flex items-center gap-1.5">
                                <span>Buka Tautan Sumber Resmi</span>
                                <span>↗</span>
                            </a>
                            <a href="{{ route('library.index') }}" class="px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 bg-white">Kembali ke Library</a>
                        </div>
                    @else
                        <p class="text-xs text-red-500">Tautan sumber belum tersedia.</p>
                    @endif

                    <div class="pt-2 border-t border-blue-100/70">
                        @if(($progress->progress_percent ?? 0) >= 100)
                            <span class="inline-block px-4 py-2 bg-green-50 text-green-700 rounded-lg text-sm font-medium">✓ Selesai dipelajari</span>
                        @else
                            <button id="link-complete-btn" type="button" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark">Tandai selesai membaca</button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if($learningMaterial->isNative())
    <script>
    (function() {
        var progressUrl = "{{ route('library.progress.update', $learningMaterial) }}";
        var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        var current = {{ (int) ($progress->progress_percent ?? 0) }};
        var lastSent = current;
        var pending = null;
        var bar = document.getElementById('progress-bar');
        var val = document.getElementById('progress-value');
        var status = document.getElementById('progress-status');
        function calc() {
            var docH = document.documentElement.scrollHeight;
            var winH = window.innerHeight || document.documentElement.clientHeight;
            var scrollable = docH - winH;
            if (scrollable <= 0) return 100;
            var top = window.scrollY || document.documentElement.scrollTop;
            var p = Math.round((top / scrollable) * 100);
            if (p < 0) p = 0;
            if (p > 100) p = 100;
            return p;
        }
        function render(p) {
            if (bar) bar.style.width = p + '%';
            if (val) val.textContent = p + '%';
            if (status) status.textContent = p >= 100 ? '✓ Selesai dibaca' : (p > 0 ? 'Sedang membaca' : 'Belum mulai');
        }
        function send(p) {
            if (p <= lastSent) return;
            lastSent = p;
            fetch(progressUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ progress_percent: p })
            }).then(function(r){ return r.json(); }).then(function(d){
                if (d && typeof d.progress_percent !== 'undefined') render(d.progress_percent);
            }).catch(function(){});
        }
        function onScroll() {
            var p = calc();
            render(Math.max(current, p) > lastSent ? p : lastSent);
            if (p <= lastSent) return;
            if (pending) clearTimeout(pending);
            pending = setTimeout(function(){ send(p); }, 400);
        }
        var init = calc();
        if (init > current) {
            render(init);
            if (init > lastSent) send(init);
        }
        window.addEventListener('scroll', onScroll, { passive: true });
    })();
    </script>
    @endif

    @if($learningMaterial->isPdf() || $learningMaterial->isLinkOnly())
    <script>
    (function() {
        var btn = document.getElementById('pdf-complete-btn') || document.getElementById('link-complete-btn');
        if (!btn) return;
        var url = "{{ route('library.progress.update', $learningMaterial) }}";
        var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        var val = document.getElementById('progress-value');
        var status = document.getElementById('progress-status');
        btn.addEventListener('click', function() {
            btn.disabled = true;
            btn.textContent = 'Menyimpan...';
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ progress_percent: 100 })
            }).then(function(r){ return r.json(); }).then(function(d){
                if (val) val.textContent = '100%';
                if (status) status.textContent = '✓ Selesai dibaca';
                btn.outerHTML = '<span class="inline-block px-4 py-2 bg-green-50 text-green-700 rounded-lg text-sm font-medium">✓ Selesai dibaca</span>';
            }).catch(function(){ btn.disabled = false; btn.textContent = 'Tandai selesai membaca'; });
        });
    })();
    </script>
    @endif
</x-app-layout>
