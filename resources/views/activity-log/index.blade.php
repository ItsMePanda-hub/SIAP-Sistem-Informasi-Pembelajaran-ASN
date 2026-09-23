<x-app-layout>
    <x-slot name="header">Log Aktivitas</x-slot>
    <div class="max-w-5xl mx-auto space-y-4">
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500">
                        <tr>
                            <th class="px-4 py-2 text-left">Waktu</th>
                            <th class="px-4 py-2 text-left">User</th>
                            <th class="px-4 py-2 text-left">Aksi</th>
                            <th class="px-4 py-2 text-left">Model</th>
                            <th class="px-4 py-2 text-left">Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="px-4 py-2 text-xs text-gray-500">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2">{{ $log->user->name ?? '-' }}</td>
                                <td class="px-4 py-2"><span class="px-2 py-0.5 rounded-full text-xs {{ $log->action==='created'?'bg-green-50 text-green-600':($log->action==='deleted'?'bg-red-50 text-red-600':'bg-blue-50 text-blue-600') }}">{{ $log->action }}</span></td>
                                <td class="px-4 py-2 text-xs">{{ class_basename($log->model_type) }} #{{ $log->model_id }}</td>
                                <td class="px-4 py-2 text-xs text-gray-600">{{ $log->description }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Belum ada aktivitas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div>{{ $logs->links() }}</div>
    </div>
</x-app-layout>
