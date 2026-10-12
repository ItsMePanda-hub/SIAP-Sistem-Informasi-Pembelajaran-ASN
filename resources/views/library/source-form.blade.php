<x-app-layout>
    <x-slot name="header">Provenance &amp; Hak Cipta — {{ $material->title }}</x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
            <div class="mb-4 text-xs text-gray-500 space-y-1">
                <div>Scope: <strong>{{ $material->scope->value }}</strong> · Tipe: <strong>{{ $material->material_type->value }}</strong> · Verifikasi: {{ $material->verification_status->value }} · Validitas: {{ $material->validity_status->value }} · Publikasi: {{ $material->publication_status->value }}</div>
                @if($material->uploaded_by)
                    <div>Pengunggah (Uploader): <strong>{{ $material->uploader->name ?? 'User #'.$material->uploaded_by }}</strong></div>
                @endif
                @if($material->isCore())
                    <div class="text-amber-600 font-medium">Core wajib memiliki Sumber, Penerbit, dan Persetujuan Hak Cipta (Rights Review) sebelum dapat dipublikasikan.</div>
                @endif
            </div>

            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('library.source.update', $material) }}">
                @csrf @method('PUT')

                <div class="border-b border-gray-100 pb-4 mb-4 space-y-4">
                    <div class="text-sm font-semibold text-gray-800">1. Identitas Sumber &amp; Penerbit</div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sumber (source_name) @if($material->isCore())<span class="text-red-600">*</span>@endif</label>
                        <input type="text" name="source_name" value="{{ old('source_name', $source->source_name ?? '') }}" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:border-primary focus:ring-primary" @if($material->isCore()) required @endif>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Penerbit (publisher) @if($material->isCore())<span class="text-red-600">*</span>@endif</label>
                        <input type="text" name="publisher" value="{{ old('publisher', $source->publisher ?? '') }}" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:border-primary focus:ring-primary" @if($material->isCore()) required @endif>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL Sumber (source_url) @if($material->isLinkOnly())<span class="text-red-600">* (Wajib untuk Link Only)</span>@endif</label>
                        <input type="url" name="source_url" value="{{ old('source_url', $source->source_url ?? '') }}" placeholder="https://" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:border-primary focus:ring-primary" @if($material->isLinkOnly()) required @endif>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Sumber</label>
                            <input type="text" name="source_type" value="{{ old('source_type', $source->source_type ?? '') }}" placeholder="Contoh: peraturan, buku_pedoman" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:border-primary focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Identitas Dokumen</label>
                            <input type="text" name="document_identifier" value="{{ old('document_identifier', $source->document_identifier ?? '') }}" placeholder="Contoh: PerLAN No. 10/2023" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:border-primary focus:ring-primary">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Versi</label>
                            <input type="text" name="version" value="{{ old('version', $source->version ?? '') }}" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:border-primary focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Terbit</label>
                            <input type="date" name="source_issued_at" value="{{ old('source_issued_at', isset($source->source_issued_at) ? \Illuminate\Support\Carbon::parse($source->source_issued_at)->format('Y-m-d') : '') }}" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:border-primary focus:ring-primary">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Terakhir Diperiksa</label>
                        <input type="date" name="last_checked_at" value="{{ old('last_checked_at', isset($source->last_checked_at) ? \Illuminate\Support\Carbon::parse($source->last_checked_at)->format('Y-m-d') : '') }}" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:border-primary focus:ring-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Verifikasi Keaslian</label>
                        <textarea name="verification_notes" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:border-primary focus:ring-primary">{{ old('verification_notes', $source->verification_notes ?? '') }}</textarea>
                    </div>
                </div>

                <div class="space-y-4 mb-6">
                    <div class="text-sm font-semibold text-gray-800">2. Lisensi &amp; Peninjauan Hak Cipta (Rights Review)</div>
                    <div class="text-xs text-gray-500 bg-gray-50 border border-gray-100 rounded-lg p-3">
                        Peninjauan hak cipta terpisah dari verifikasi keaslian materi. Untuk materi Core berformat Native/PDF, hak redistribusi (CC BY, CC BY-SA, atau Izin Tertulis) wajib ditinjau dan disetujui oleh manajer yang bukan pengunggah materi.
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Klasifikasi Lisensi @if($material->isCore())<span class="text-red-600">*</span>@endif</label>
                        <select name="license_type" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:border-primary focus:ring-primary" @if($material->isCore()) required @endif>
                            <option value="">-- Pilih Lisensi --</option>
                            @foreach($licenses as $lic)
                                <option value="{{ $lic->value }}" @selected(old('license_type', $source->license_type->value ?? ($source->license_type ?? '')) === $lic->value)>
                                    {{ $lic->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL Bukti Lisensi / Izin</label>
                        <input type="url" name="license_url" value="{{ old('license_url', $source->license_url ?? '') }}" placeholder="https://creativecommons.org/licenses/by/4.0/ atau URL izin" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:border-primary focus:ring-primary">
                        <p class="text-xs text-gray-400 mt-1">Wajib diisi jika lisensi CC atau izin tertulis.</p>
                    </div>

                    <div class="p-4 bg-gray-50/70 border border-gray-200 rounded-lg space-y-3">
                        <div class="font-medium text-xs text-gray-700 uppercase tracking-wide">Status Keputusan Peninjauan Hak (Rights Review)</div>

                        @if($material->uploaded_by && (int) $material->uploaded_by === (int) auth()->id())
                            <div class="p-2 bg-amber-50 text-amber-800 text-xs rounded border border-amber-200">
                                <strong>Pencegahan Self-Approval:</strong> Anda adalah pengunggah materi ini. Status persetujuan ('approved') hanya dapat diberikan oleh manajer perpustakaan yang lain.
                            </div>
                        @endif

                        @if($source && $source->rights_reviewed_by)
                            <div class="text-xs text-gray-600">
                                Ditinjau oleh: <strong>{{ $source->rightsReviewer->name ?? 'User #'.$source->rights_reviewed_by }}</strong> pada <strong>{{ $source->rights_reviewed_at?->format('d/m/Y H:i') }}</strong>
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Keputusan Hak Cipta</label>
                            <select name="rights_status" class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-white focus:border-primary focus:ring-primary">
                                <option value="unreviewed" @selected(old('rights_status', $source->rights_status ?? 'unreviewed') === 'unreviewed')>Belum Ditinjau (Unreviewed)</option>
                                <option value="approved" @selected(old('rights_status', $source->rights_status ?? 'unreviewed') === 'approved')>Disetujui untuk Publikasi (Approved)</option>
                                <option value="rejected" @selected(old('rights_status', $source->rights_status ?? 'unreviewed') === 'rejected')>Ditolak Redistribusi (Rejected)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Peninjauan Hak Cipta</label>
                            <textarea name="rights_review_notes" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-white focus:border-primary focus:ring-primary" placeholder="Catatan izin, verifikasi lisensi, atau batasan penggunaan...">{{ old('rights_review_notes', $source->rights_review_notes ?? '') }}</textarea>
                        </div>

                        @if(isset($rightsLogs) && $rightsLogs->isNotEmpty())
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <div class="text-xs font-semibold text-gray-700 mb-2">Riwayat Keputusan Hak Cipta (Audit Trail)</div>
                                <div class="space-y-2 max-h-48 overflow-y-auto">
                                    @foreach($rightsLogs as $log)
                                        <div class="text-xs p-2.5 bg-white border border-gray-100 rounded-lg flex items-start justify-between gap-2">
                                            <div>
                                                <div class="font-medium text-gray-800">
                                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase {{ $log->decision === 'approved' ? 'bg-green-100 text-green-800' : ($log->decision === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-700') }}">
                                                        {{ $log->decision }}
                                                    </span>
                                                    <span class="ml-1 text-gray-600">oleh {{ $log->reviewer->name ?? ($log->reviewer_user_id ? 'User #'.$log->reviewer_user_id : 'Sistem (Otomatis)') }}</span>
                                                </div>
                                                @if($log->notes)
                                                    <div class="text-gray-500 mt-1 italic">"{{ $log->notes }}"</div>
                                                @endif
                                                @if($log->license_type)
                                                    <div class="text-[11px] text-gray-400 mt-0.5">Lisensi: {{ $log->license_type->value ?? $log->license_type }}</div>
                                                @endif
                                            </div>
                                            <div class="text-[11px] text-gray-400 flex-shrink-0">
                                                {{ $log->created_at->format('d/m/Y H:i') }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if(isset($lifecycleLogs) && $lifecycleLogs->isNotEmpty())
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <div class="text-xs font-semibold text-gray-700 mb-2">Riwayat Perubahan Status &amp; Metadata (Lifecycle Audit)</div>
                                <div class="space-y-2 max-h-48 overflow-y-auto">
                                    @foreach($lifecycleLogs as $log)
                                        <div class="text-xs p-2.5 bg-white border border-gray-100 rounded-lg flex items-start justify-between gap-2">
                                            <div>
                                                <div class="font-medium text-gray-800">
                                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase bg-blue-50 text-blue-700">{{ $log->field }}</span>
                                                    <span class="ml-1 text-gray-600">{{ $log->old_value ?? '—' }} → {{ $log->new_value ?? '—' }}</span>
                                                </div>
                                                <div class="text-[11px] text-gray-400 mt-0.5">
                                                    oleh {{ $log->actor->name ?? ($log->actor_user_id ? 'User #'.$log->actor_user_id : 'Sistem') }}
                                                    @if($log->is_system)<span class="ml-1 px-1 py-0.5 bg-gray-100 rounded">sistem</span>@endif
                                                </div>
                                                @if($log->reason)
                                                    <div class="text-gray-500 mt-1 italic">"{{ $log->reason }}"</div>
                                                @endif
                                            </div>
                                            <div class="text-[11px] text-gray-400 flex-shrink-0">{{ $log->created_at->format('d/m/Y H:i') }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark">Simpan Provenance &amp; Hak Cipta</button>
                    <a href="{{ route('library.index') }}" class="px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-600">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
