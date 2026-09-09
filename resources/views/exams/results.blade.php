<x-app-layout>
    <x-slot name="header">Hasil Ujian: {{ $exam->title }}</x-slot>

    <div class="max-w-4xl mx-auto space-y-4">
        <a href="{{ route('exams.show', $exam) }}" class="text-xs text-gray-400 hover:text-primary-dark">&larr; Kembali ke ujian</a>

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 divide-y divide-gray-100">
            @forelse ($attempts as $attempt)
                <div class="p-5">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-semibold text-gray-800">{{ $attempt->user->name }}</div>
                            <div class="text-xs text-gray-400">
                                Status:
                                <span class="{{ $attempt->status === 'selesai_pelanggaran' ? 'text-orange-600 font-medium' : '' }}">
                                    {{ ['sedang_berjalan' => 'Sedang mengerjakan', 'selesai' => 'Selesai', 'selesai_pelanggaran' => 'Selesai (pelanggaran)', 'menunggu_penilaian_esai' => 'Menunggu penilaian esai'][$attempt->status] ?? $attempt->status }}
                                </span>
                                @if ($attempt->violation_count > 0)
                                    &middot; {{ $attempt->violation_count }} peringatan
                                @endif
                            </div>
                        </div>
                        @if ($attempt->score !== null && $attempt->status !== 'menunggu_penilaian_esai')
                            <div class="text-right">
                                <div class="text-lg font-semibold text-primary-dark">{{ $attempt->score }}</div>
                                <div class="text-[11px] text-gray-400">skor akhir</div>
                            </div>
                        @elseif ($attempt->status === 'menunggu_penilaian_esai')
                            <div class="text-right">
                                <div class="text-[11px] text-gray-400">menunggu dinilai</div>
                            </div>
                        @endif
                    </div>

                    @php $essays = $attempt->answers->filter(fn ($a) => $a->question->type === 'esai'); @endphp
                    @if ($essays->isNotEmpty())
                        <div class="mt-4 space-y-3">
                            @foreach ($essays as $ans)
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <div class="text-xs text-gray-500 mb-1">{{ $ans->question->question }}</div>
                                    <div class="text-sm text-gray-800 mb-2">{{ $ans->essay_answer ?: '(belum dijawab)' }}</div>
                                    <form method="POST" action="{{ route('exams.grade-essay', $ans) }}" class="flex gap-2 items-center">
                                        @csrf
                                        <span class="text-xs text-gray-400">Nilai:</span>
                                        <button name="is_correct" value="1" class="text-xs px-2 py-1 rounded-full {{ $ans->essay_graded_correct === true ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">Benar</button>
                                        <button name="is_correct" value="0" class="text-xs px-2 py-1 rounded-full {{ $ans->essay_graded_correct === false ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500' }}">Kurang tepat</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($attempt->violation_appeal_status === 'diajukan')
                        <div class="mt-4 bg-orange-50 rounded-lg p-3 border border-orange-100 flex justify-between items-center">
                            <div>
                                <div class="text-xs font-semibold text-orange-800">Banding diajukan:</div>
                                <div class="text-sm text-orange-700 italic">"{{ $attempt->violation_appeal_note }}"</div>
                            </div>
                            <form method="POST" action="{{ route('exams.resolve-appeal', $attempt) }}" class="flex gap-2">
                                @csrf
                                <button name="action" value="terima" class="px-2 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">Terima</button>
                                <button name="action" value="tolak" class="px-2 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700">Tolak</button>
                            </form>
                        </div>
                    @elseif ($attempt->violation_appeal_status)
                        <div class="mt-4 bg-gray-50 rounded-lg p-3 text-xs text-gray-500">
                            Banding <span class="font-semibold">{{ $attempt->violation_appeal_status }}</span>: "{{ $attempt->violation_appeal_note }}"
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-gray-400 text-sm p-5">Belum ada yang mengerjakan ujian ini.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
