<x-app-layout>
    <x-slot name="header">Atur Pengguna: {{ $user->name }}</x-slot>

    <div class="max-w-xl mx-auto">
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf
                @method('PUT')

                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" id="role" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-4 focus:border-primary focus:ring-primary">
                    <option value="pegawai" {{ $user->role === 'pegawai' ? 'selected' : '' }}>Pegawai</option>
                    <option value="atasan" {{ $user->role === 'atasan' ? 'selected' : '' }}>Atasan</option>
                    <option value="pemilik" {{ $user->role === 'pemilik' ? 'selected' : '' }}>Pemilik</option>
                    <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>

                <label class="block text-sm font-medium text-gray-700 mb-1">Unit Kerja / Bidang</label>
                <input type="text" name="unit_kerja" value="{{ $user->unit_kerja }}" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-4 focus:border-primary focus:ring-primary">

                <label class="block text-sm font-medium text-gray-700 mb-1">Jabatan</label>
                <input type="text" name="jabatan" value="{{ $user->jabatan }}" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-4 focus:border-primary focus:ring-primary">

                <label class="block text-sm font-medium text-gray-700 mb-1">NIP</label>
                <input type="text" name="nip" value="{{ $user->nip }}" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-4 focus:border-primary focus:ring-primary">

                <div id="atasan-field" class="{{ $user->role === 'pegawai' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Atasan Langsung</label>
                    <select name="atasan_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-4 focus:border-primary focus:ring-primary">
                        <option value="">- Belum ditentukan -</option>
                        @foreach ($atasanList as $a)
                            <option value="{{ $a->id }}" {{ $user->atasan_id == $a->id ? 'selected' : '' }}>{{ $a->name }} ({{ $a->unit_kerja }})</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark">
                    Simpan
                </button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('role').addEventListener('change', function () {
            document.getElementById('atasan-field').classList.toggle('hidden', this.value !== 'pegawai');
        });
    </script>
</x-app-layout>
