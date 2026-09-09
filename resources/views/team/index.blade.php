<x-app-layout>
    <x-slot name="header">Tim Saya</x-slot>

    <div class="max-w-6xl mx-auto space-y-4">
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-4 py-3">Nama</th>
                        <th class="text-left px-4 py-3">Unit Kerja</th>
                        <th class="text-left px-4 py-3">Peran</th>
                        <th class="text-left px-4 py-3">Atasan</th>
                        <th class="text-center px-4 py-3">Status Ujian</th>
                        <th class="text-center px-4 py-3">Pelatihan</th>
                        <th class="text-center px-4 py-3">Pengumuman (Unread)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roster as $person)
                        <tr class="border-t border-gray-100">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $person['name'] }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $person['unit_kerja'] ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded-full bg-primary-light text-primary-dark capitalize">{{ $person['role'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $person['atasan_name'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                @if ($person['exam_completed'])
                                    <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-700">Sudah</span>
                                @else
                                    <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-500">Belum</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600">
                                {{ $person['training_progress_percent'] }}%
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600">
                                @if ($person['announcement_unread_count'] > 0)
                                    <span class="text-red-500 font-bold">{{ $person['announcement_unread_count'] }}</span>
                                @else
                                    0
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">Tidak ada data tim yang dapat ditampilkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
