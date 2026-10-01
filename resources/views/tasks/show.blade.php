<x-app-layout>
    <x-slot name="header">Workspace - {{ $task->title }}</x-slot>

    <div class="max-w-5xl mx-auto space-y-6">
        <a href="{{ route('tasks.index') }}" class="text-xs text-gray-400 hover:text-primary transition">&larr; Kembali ke Workspace</a>

        {{-- Detail Task --}}
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6 space-y-4">
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 border-b border-gray-100 pb-4">
                <div>
                    <h1 class="text-xl font-bold text-gray-800">{{ $task->title }}</h1>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-400 mt-2">
                        <span>Dibuat oleh: <strong class="text-gray-600">{{ $task->creator->name ?? 'Admin' }}</strong> ({{ $task->creator->unit_kerja ?? 'Sistem' }})</span>
                        <span>&bull;</span>
                        <span>Dibuat pada: {{ $task->created_at->translatedFormat('d M Y, H:i') }}</span>
                    </div>
                </div>

                <div class="text-right">
                    <div class="text-xs text-gray-400">Tenggat Waktu (Deadline)</div>
                    <div class="text-sm font-semibold {{ now()->gt($task->deadline) ? 'text-red-600' : 'text-primary-dark' }}">
                        {{ $task->deadline->translatedFormat('d F Y, H:i') }}
                    </div>
                    @if (now()->gt($task->deadline))
                        <span class="inline-block text-[10px] px-2 py-0.5 rounded bg-red-50 text-red-600 font-medium mt-1">Deadline Berakhir</span>
                    @else
                        <span class="inline-block text-[10px] px-2 py-0.5 rounded bg-green-50 text-green-600 font-medium mt-1">Aktif</span>
                    @endif
                </div>
            </div>

            <div>
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Deskripsi & Instruksi</h3>
                <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-line bg-gray-50/50 p-4 rounded-lg border border-gray-100">
                    {{ $task->description }}
                </div>
            </div>

            @if ($task->attachment_path)
                <div class="p-4 bg-blue-50 border border-blue-100 rounded-lg flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-gray-800">Lampiran / Berkas Panduan Task</div>
                        <div class="text-[11px] text-gray-500">Unduh berkas panduan yang disediakan pembuat task.</div>
                    </div>
                    <a href="{{ route('tasks.attachment', $task) }}"
                       class="inline-flex items-center px-3.5 py-1.5 bg-primary text-white rounded-lg text-xs font-medium hover:bg-primary-dark transition">
                        Buka / Unduh Lampiran
                    </a>
                </div>
            @endif
        </div>

        {{-- Tampilan Pegawai --}}
        @if (auth()->user()->role === 'pegawai' && $myAssignment)
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6 space-y-6">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                    <h2 class="text-base font-semibold text-gray-800">Status Pekerjaan Anda</h2>
                    <span class="text-xs px-3 py-1 rounded-full font-semibold
                        @if($myAssignment->status === 'selesai') bg-green-50 text-green-700 border border-green-200
                        @elseif($myAssignment->status === 'menunggu_review') bg-[#FBF7EE] text-[#C9A15C] border border-[#EEDEB8]
                        @elseif($myAssignment->status === 'revisi') bg-red-50 text-red-700 border border-red-200
                        @else bg-gray-100 text-gray-600 @endif">
                        {{ [
                            'belum_dikerjakan' => 'Belum Dikerjakan',
                            'menunggu_review' => 'Menunggu Review Atasan',
                            'revisi' => 'Perlu Revisi',
                            'selesai' => 'Selesai'
                        ][$myAssignment->status] ?? $myAssignment->status }}
                    </span>
                </div>

                {{-- Feedback jika ada revisi atau catatan review --}}
                @if ($myAssignment->feedback)
                    <div class="p-4 rounded-lg {{ $myAssignment->status === 'revisi' ? 'bg-red-50 border border-red-200 text-red-800' : 'bg-green-50 border border-green-200 text-green-800' }}">
                        <div class="font-semibold text-xs mb-1">Catatan / Feedback Reviewer:</div>
                        <p class="text-sm whitespace-pre-line">{{ $myAssignment->feedback }}</p>
                    </div>
                @endif

                {{-- File yang sudah dikumpulkan sebelumnya --}}
                @if ($myAssignment->submission_path)
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-100 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Berkas Pekerjaan Terakhir:</span>
                            @if ($myAssignment->is_late)
                                <span class="text-[11px] font-medium text-red-600 bg-red-50 px-2 py-0.5 rounded">Dikumpulkan Terlambat</span>
                            @else
                                <span class="text-[11px] font-medium text-green-600 bg-green-50 px-2 py-0.5 rounded">Tepat Waktu</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between">
                            <a href="{{ route('tasks.download', $myAssignment) }}" class="text-sm font-medium text-primary hover:text-primary-dark underline">
                                Unduh Berkas Pengumpulan
                            </a>
                            <span class="text-xs text-gray-400">
                                Dikumpulkan: {{ $myAssignment->submitted_at?->translatedFormat('d M Y, H:i') }}
                            </span>
                        </div>
                        @if ($myAssignment->submission_note)
                            <div class="text-xs text-gray-600 pt-1">
                                <strong>Catatan:</strong> {{ $myAssignment->submission_note }}
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Form submit jika belum selesai atau perlu revisi --}}
                @if (in_array($myAssignment->status, ['belum_dikerjakan', 'revisi']))
                    <div class="pt-2">
                        <h3 class="text-sm font-semibold text-gray-800 mb-3">
                            {{ $myAssignment->status === 'revisi' ? 'Unggah Berkas Revisi' : 'Kumpulkan Hasil Pekerjaan' }}
                        </h3>

                        @if ($errors->any())
                            <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg text-xs space-y-1">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('tasks.submit', $myAssignment) }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Unggah Berkas Pekerjaan <span class="text-red-500">*</span></label>
                                <p class="text-[11px] text-gray-400 mb-2">Format: PDF, Word, PowerPoint, Excel, ZIP, MP4, JPG, PNG (maks 50MB)</p>
                                <input type="file" name="file" required accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.mp4,.jpg,.jpeg,.png"
                                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Catatan untuk Reviewer (opsional)</label>
                                <textarea name="note" rows="3" placeholder="Tuliskan catatan tambahan mengenai pekerjaan ini jika ada..."
                                          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:border-primary focus:ring-primary">{{ old('note') }}</textarea>
                            </div>

                            <button type="submit" class="px-5 py-2.5 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark transition">
                                Kumpulkan Pekerjaan
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @endif

        {{-- Tampilan Admin / Atasan / Pemilik --}}
        @if (in_array(auth()->user()->role, ['admin', 'pemilik', 'atasan']))
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h2 class="text-base font-semibold text-gray-800">Daftar Pegawai & Status Pengumpulan</h2>
                    <span class="text-xs text-gray-400">Total: {{ $task->assignments->count() }} Pegawai</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50/70 text-xs text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-4">Pegawai</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Pengumpulan</th>
                                <th class="py-3 px-4">Aksi / Review</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($task->assignments as $assignment)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-800">{{ $assignment->user->name }}</div>
                                        <div class="text-xs text-gray-400">{{ $assignment->user->unit_kerja }} &bull; {{ $assignment->user->jabatan ?? 'Pegawai' }}</div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-[11px] px-2.5 py-0.5 rounded-full font-medium
                                            @if($assignment->status === 'selesai') bg-green-50 text-green-700 border border-green-200
                                            @elseif($assignment->status === 'menunggu_review') bg-[#FBF7EE] text-[#C9A15C] border border-[#EEDEB8]
                                            @elseif($assignment->status === 'revisi') bg-red-50 text-red-700 border border-red-200
                                            @else bg-gray-100 text-gray-600 @endif">
                                            {{ [
                                                'belum_dikerjakan' => 'Belum Dikerjakan',
                                                'menunggu_review' => 'Menunggu Review',
                                                'revisi' => 'Perlu Revisi',
                                                'selesai' => 'Selesai'
                                            ][$assignment->status] ?? $assignment->status }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-xs">
                                        @if ($assignment->submission_path)
                                            <div>
                                                <a href="{{ route('tasks.download', $assignment) }}" class="text-primary hover:text-primary-dark font-medium underline">
                                                    Unduh Berkas
                                                </a>
                                            </div>
                                            <div class="text-gray-400 text-[11px] mt-0.5">
                                                {{ $assignment->submitted_at?->translatedFormat('d M, H:i') }}
                                                @if ($assignment->is_late)
                                                    <span class="text-red-500 font-semibold">(Terlambat)</span>
                                                @endif
                                            </div>
                                            @if ($assignment->submission_note)
                                                <div class="text-gray-500 italic text-[11px] mt-1 bg-gray-50 p-1.5 rounded">
                                                    "{{ $assignment->submission_note }}"
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-gray-400 italic">Belum mengumpulkan</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-xs">
                                        @php
                                            $canReview = (auth()->user()->role === 'admin') ||
                                                         (auth()->user()->role === 'atasan' && auth()->user()->unit_kerja === $assignment->user->unit_kerja);
                                        @endphp

                                        @if ($canReview && $assignment->status === 'menunggu_review')
                                            <div x-data="{ openReview: false }" class="space-y-2">
                                                <button @click="openReview = !openReview" class="px-2.5 py-1 bg-primary text-white rounded text-xs font-medium hover:bg-primary-dark transition">
                                                    Beri Review
                                                </button>

                                                <div x-show="openReview" x-cloak class="mt-2 p-3 bg-gray-50 rounded-lg border border-gray-200 space-y-2">
                                                    <form method="POST" action="{{ route('tasks.review', $assignment) }}">
                                                        @csrf
                                                        <div class="flex items-center gap-3 mb-2">
                                                            <label class="inline-flex items-center gap-1 text-xs cursor-pointer">
                                                                <input type="radio" name="decision" value="selesai" required class="text-green-600 focus:ring-green-500">
                                                                <span class="text-green-700 font-medium">Terima (Selesai)</span>
                                                            </label>
                                                            <label class="inline-flex items-center gap-1 text-xs cursor-pointer">
                                                                <input type="radio" name="decision" value="revisi" required class="text-red-600 focus:ring-red-500">
                                                                <span class="text-red-700 font-medium">Minta Revisi</span>
                                                            </label>
                                                        </div>

                                                        <textarea name="feedback" rows="2" placeholder="Catatan review (wajib jika minta revisi)..."
                                                                  class="w-full border border-gray-300 rounded p-1.5 text-xs focus:border-primary focus:ring-primary"></textarea>

                                                        <div class="flex justify-end gap-2 pt-1">
                                                            <button type="button" @click="openReview = false" class="px-2 py-1 text-gray-500 text-xs">Tutup</button>
                                                            <button type="submit" class="px-3 py-1 bg-primary text-white rounded text-xs font-medium">Kirim</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        @elseif ($assignment->reviewed_at)
                                            <div class="text-[11px] text-gray-500">
                                                Direview oleh: <strong>{{ $assignment->reviewer->name ?? 'Reviewer' }}</strong><br>
                                                Pada: {{ $assignment->reviewed_at->translatedFormat('d M Y, H:i') }}
                                                @if ($assignment->feedback)
                                                    <div class="mt-1 italic text-gray-600">"{{ $assignment->feedback }}"</div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-gray-400 text-xs">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-gray-400 text-xs">
                                        Tidak ada penugasan untuk task ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
