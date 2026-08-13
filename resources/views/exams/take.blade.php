<x-app-layout>
    <x-slot name="header">{{ $exam->title }} — sedang dikerjakan</x-slot>

    <div class="max-w-2xl mx-auto space-y-6" id="exam-root"
         data-exam-id="{{ $exam->id }}"
         data-answer-url="{{ route('exams.answer', $exam) }}"
         data-violation-url="{{ route('exams.violation', $exam) }}"
         data-submit-url="{{ route('exams.submit', $exam) }}"
         data-show-url="{{ route('exams.show', $exam) }}">

        <div class="flex items-center justify-between">
            <span class="inline-flex items-center gap-2 bg-green-50 text-green-700 text-xs font-semibold px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Mode fokus aktif
            </span>
            <span id="violation-badge" class="bg-orange-50 text-orange-600 text-xs font-semibold px-3 py-1.5 rounded-full">
                0 dari {{ $exam->max_violations }} peringatan
            </span>
        </div>

        @foreach ($exam->questions as $q)
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
                <div class="text-xs text-gray-400 mb-2">Soal {{ $loop->iteration }}</div>
                <p class="font-medium text-gray-800 mb-4">{{ $q->question }}</p>

                @if ($q->type === 'pilihan_ganda')
                    @foreach ($q->options as $opt)
                        <label class="flex items-center gap-2 border border-gray-200 rounded-lg px-3 py-2 mb-2 cursor-pointer hover:border-primary text-sm">
                            <input type="radio" name="q_{{ $q->id }}" value="{{ $opt->id }}"
                                   class="answer-input" data-question-id="{{ $q->id }}" data-type="pilihan_ganda"
                                   {{ optional($existingAnswers->get($q->id))->exam_option_id == $opt->id ? 'checked' : '' }}>
                            {{ $opt->option_text }}
                        </label>
                    @endforeach
                @else
                    <textarea rows="4" class="answer-input w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"
                              data-question-id="{{ $q->id }}" data-type="esai">{{ optional($existingAnswers->get($q->id))->essay_answer }}</textarea>
                @endif
            </div>
        @endforeach

        <div class="flex justify-between items-center pb-8">
            <span class="text-xs text-gray-400">Jawaban tersimpan otomatis</span>
            <button id="submit-btn" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark">
                Selesai &amp; Kumpulkan
            </button>
        </div>
    </div>

    <div id="violation-overlay" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 max-w-sm mx-4">
            <div class="w-10 h-10 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center font-bold mb-3">!</div>
            <h3 id="violation-title" class="font-semibold text-gray-800 mb-2">Aktivitas mencurigakan terdeteksi</h3>
            <p id="violation-text" class="text-sm text-gray-500 mb-5"></p>
            <button id="violation-ok" class="w-full px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium">Mengerti</button>
        </div>
    </div>

    <script>
    (function () {
        const root = document.getElementById('exam-root');
        const answerUrl = root.dataset.answerUrl;
        const violationUrl = root.dataset.violationUrl;
        const submitUrl = root.dataset.submitUrl;
        const showUrl = root.dataset.showUrl;
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const overlay = document.getElementById('violation-overlay');
        const badge = document.getElementById('violation-badge');
        let finished = false;

        function post(url, body) {
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(body),
            }).then(r => r.json());
        }

        document.querySelectorAll('.answer-input').forEach(input => {
            const eventName = input.tagName === 'TEXTAREA' ? 'blur' : 'change';
            input.addEventListener(eventName, () => {
                const qid = input.dataset.questionId;
                const type = input.dataset.type;
                const payload = { exam_question_id: qid };
                if (type === 'pilihan_ganda') {
                    payload.exam_option_id = input.value;
                } else {
                    payload.essay_answer = input.value;
                }
                post(answerUrl, payload);
            });
        });

        document.addEventListener('visibilitychange', () => {
            if (document.hidden && !finished) reportViolation();
        });

        function reportViolation() {
            post(violationUrl, {}).then(data => {
                badge.textContent = `${data.violation_count} dari ${data.max_violations} peringatan`;
                const title = document.getElementById('violation-title');
                const text = document.getElementById('violation-text');
                if (data.is_final) {
                    finished = true;
                    title.textContent = 'Ujian dikumpulkan otomatis';
                    text.textContent = 'Anda mencapai batas peringatan. Jawaban yang sudah diisi telah dikumpulkan dan ditandai untuk ditinjau atasan.';
                    document.getElementById('violation-ok').onclick = () => window.location.href = showUrl;
                } else {
                    title.textContent = 'Aktivitas mencurigakan terdeteksi';
                    text.textContent = 'Anda terdeteksi meninggalkan halaman ujian. Pelanggaran ini tercatat. Jawaban Anda tetap aman, silakan lanjutkan dengan jujur.';
                    document.getElementById('violation-ok').onclick = () => overlay.classList.add('hidden');
                }
                overlay.classList.remove('hidden');
                overlay.classList.add('flex');
            });
        }

        document.getElementById('submit-btn').addEventListener('click', () => {
            if (!confirm('Kumpulkan ujian sekarang?')) return;
            finished = true;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = submitUrl;
            form.innerHTML = `@csrf`;
            document.body.appendChild(form);
            form.submit();
        });
    })();
    </script>
</x-app-layout>
