<x-app-layout>
    <x-slot name="header">Workspace</x-slot>

    <div class="max-w-5xl mx-auto space-y-4">
        @if (in_array(auth()->user()->role, ['admin', 'atasan']))
            <div class="flex justify-end">
                <a href="{{ route('tasks.create') }}" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark transition">
                    + Buat Task Baru
                </a>
            </div>
        @endif

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 divide-y divide-gray-100">
            @forelse ($tasks as $task)
                @php
                    $isPegawai = auth()->user()->role === 'pegawai';
                    $myAssignment = $isPegawai ? $task->assignments->firstWhere('user_id', auth()->id()) : null;
                    $status = $myAssignment ? $myAssignment->status : null;
                @endphp
                <div class="p-6 hover:bg-gray-50/50 transition">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-1 flex-1">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('tasks.show', $task) }}" class="font-semibold text-gray-800 hover:text-primary transition text-base">
                                    {{ $task->title }}
                                </a>
                                @if ($task->target_type === 'bidang')
                                    <span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">
                                        Bidang: {{ $task->target_unit_kerja }}
                                    </span>
                                @else
                                    <span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-purple-50 text-purple-700">
                                        Individu ({{ $task->assignments->count() }} orang)
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 line-clamp-2">{{ Str::limit($task->description, 120) }}</p>
                            <div class="flex items-center gap-4 text-xs text-gray-400 pt-1">
                                <span>Dibuat oleh: <strong class="text-gray-600">{{ $task->creator->name ?? 'Admin' }}</strong></span>
                                <span>&bull;</span>
                                <span>Tenggat: <strong class="text-gray-600">{{ $task->deadline->translatedFormat('d M Y, H:i') }}</strong></span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            @if ($isPegawai && $myAssignment)
                                <span class="text-xs px-3 py-1 rounded-full font-medium
                                    @if($status === 'selesai') bg-green-50 text-green-700 border border-green-200
                                    @elseif($status === 'menunggu_review') bg-[#FBF7EE] text-[#C9A15C] border border-[#EEDEB8]
                                    @elseif($status === 'revisi') bg-red-50 text-red-700 border border-red-200
                                    @else bg-gray-100 text-gray-600 @endif">
                                    {{ [
                                        'belum_dikerjakan' => 'Belum Dikerjakan',
                                        'menunggu_review' => 'Menunggu Review',
                                        'revisi' => 'Perlu Revisi',
                                        'selesai' => 'Selesai'
                                    ][$status] ?? $status }}
                                </span>
                            @elseif (! $isPegawai)
                                @php
                                    $totalAssigned = $task->assignments->count();
                                    $completedCount = $task->assignments->where('status', 'selesai')->count();
                                    $reviewCount = $task->assignments->where('status', 'menunggu_review')->count();
                                @endphp
                                <div class="text-right text-xs">
                                    <div class="font-semibold text-gray-700">{{ $completedCount }}/{{ $totalAssigned }} Selesai</div>
                                    @if ($reviewCount > 0)
                                        <div class="text-[#C9A15C] font-medium">{{ $reviewCount }} butuh review</div>
                                    @endif
                                </div>
                            @endif

                            <a href="{{ route('tasks.show', $task) }}" class="px-3 py-1.5 bg-gray-100 hover:bg-primary hover:text-white text-gray-600 text-xs font-medium rounded-lg transition">
                                Buka Task &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-400 text-sm">
                    Belum ada task di Workspace.
                </div>
            @endforelse
        </div>

        @if ($tasks->hasPages())
            <div class="mt-4">
                {{ $tasks->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
