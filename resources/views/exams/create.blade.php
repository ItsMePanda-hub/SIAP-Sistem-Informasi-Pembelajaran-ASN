<x-app-layout>
    <x-slot name="header">Buat ujian</x-slot>

    <div class="max-w-3xl mx-auto" x-data="examForm()">
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6 mb-4">
            <form method="POST" action="{{ route('exams.store') }}" @submit="beforeSubmit">
                @csrf

                <label class="block text-sm font-medium text-gray-700 mb-1">Judul ujian</label>
                <input type="text" name="title" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-4 focus:border-primary focus:ring-primary" required>

                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="3" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-4 focus:border-primary focus:ring-primary"></textarea>

                <label class="block text-sm font-medium text-gray-700 mb-1">Terkait pelatihan (opsional)</label>
                <select name="training_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-4 focus:border-primary focus:ring-primary">
                    <option value="">- Tidak terkait pelatihan -</option>
                    @foreach ($trainings as $t)
                        <option value="{{ $t->id }}">{{ $t->title }}</option>
                    @endforeach
                </select>

                @if (in_array(auth()->user()->role, ['admin', 'pemilik']))
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kirim ke</label>
                    <select name="target_unit_kerja" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-4 focus:border-primary focus:ring-primary">
                        <option value="">Semua bidang</option>
                        @foreach ($unitKerjaList as $unit)
                            <option value="{{ $unit }}">{{ $unit }}</option>
                        @endforeach
                    </select>
                @endif

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Durasi (menit, opsional)</label>
                        <input type="number" name="duration_minutes" min="1" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:border-primary focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Maksimal peringatan</label>
                        <input type="number" name="max_violations" value="3" min="1" max="10" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:border-primary focus:ring-primary" required>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4 mb-4">
                    <h3 class="font-semibold text-gray-800 mb-3">Soal ujian</h3>

                    <template x-for="(q, qi) in questions" :key="qi">
                        <div class="border border-gray-200 rounded-lg p-4 mb-4">
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-xs font-semibold text-gray-500">Soal <span x-text="qi + 1"></span></span>
                                <button type="button" @click="removeQuestion(qi)" class="text-xs text-red-500 hover:underline">Hapus soal</button>
                            </div>

                            <select :name="`questions[${qi}][type]`" x-model="q.type" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-3 text-sm">
                                <option value="pilihan_ganda">Pilihan ganda</option>
                                <option value="esai">Esai</option>
                            </select>

                            <textarea :name="`questions[${qi}][question]`" x-model="q.question" rows="2" placeholder="Tulis pertanyaan..." class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-3 text-sm" required></textarea>

                            <template x-if="q.type === 'pilihan_ganda'">
                                <div class="space-y-2">
                                    <template x-for="(opt, oi) in q.options" :key="oi">
                                        <div class="flex items-center gap-2">
                                            <input type="radio" :name="`questions[${qi}][correct_index]`" :value="oi" x-model.number="q.correct_index">
                                            <input type="text" :name="`questions[${qi}][options][${oi}][text]`" x-model="opt.text" :placeholder="`Pilihan ${oi + 1}`" class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-sm">
                                        </div>
                                    </template>
                                    <p class="text-[11px] text-gray-400">Pilih radio di samping pilihan yang benar.</p>
                                </div>
                            </template>
                        </div>
                    </template>

                    <button type="button" @click="addQuestion()" class="text-sm text-primary-dark font-medium hover:underline">+ Tambah soal</button>
                </div>

                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark">
                    Simpan ujian
                </button>
            </form>
        </div>
    </div>

    <script>
        function examForm() {
            return {
                questions: [
                    { type: 'pilihan_ganda', question: '', options: [{ text: '' }, { text: '' }, { text: '' }, { text: '' }], correct_index: 0 }
                ],
                addQuestion() {
                    this.questions.push({ type: 'pilihan_ganda', question: '', options: [{ text: '' }, { text: '' }, { text: '' }, { text: '' }], correct_index: 0 });
                },
                removeQuestion(index) {
                    if (this.questions.length > 1) this.questions.splice(index, 1);
                },
            };
        }
    </script>
</x-app-layout>
