<x-app-layout>
    <x-slot name="header">Kelola Pengguna</x-slot>

    <div class="max-w-4xl mx-auto space-y-4">
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-4 py-3">Nama</th>
                        <th class="text-left px-4 py-3">Email</th>
                        <th class="text-left px-4 py-3">Role</th>
                        <th class="text-left px-4 py-3">Unit Kerja</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr class="border-t border-gray-100">
                            <td class="px-4 py-3">{{ $u->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $u->email }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded-full bg-primary-light text-primary-dark">{{ $u->role }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $u->unit_kerja ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('users.edit', $u) }}" class="text-primary-dark hover:underline">Atur</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
