<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Pengumuman
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        Edit Pengumuman: {{ $announcement->title }}
                    </h3>
                </div>

                <form method="POST" action="{{ route('announcements.update', $announcement) }}" class="p-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">
                            Judul Pengumuman
                        </label>
                        <input type="text" name="title" id="title"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                               value="{{ old('title', $announcement->title) }}" required>
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="content" class="block text-sm font-medium text-gray-700">
                            Isi Pengumuman
                        </label>
                        <textarea name="content" id="content" rows="8"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                  required>{{ old('content', $announcement->content) }}</textarea>
                        @error('content')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Visibilitas
                            </label>
                            <div class="space-y-2">
                                <div>
                                    <input type="radio" name="visibility" id="visibility_all"
                                           value="all" class="form-radio h-4 w-4 text-indigo-600"
                                           {{ old('visibility', $announcement->visibility) === 'all' ? 'checked' : '' }} required>
                                    <label for="visibility_all" class="ml-2 block text-sm font-medium text-gray-700">
                                        Semua Pengguna
                                    </label>
                                </div>
                                <div>
                                    <input type="radio" name="visibility" id="visibility_unit"
                                           value="unit" class="form-radio h-4 w-4 text-indigo-600"
                                           {{ old('visibility', $announcement->visibility) === 'unit' ? 'checked' : '' }} required>
                                    <label for="visibility_unit" class="ml-2 block text-sm font-medium text-gray-700">
                                        Unit Kerja Tertentu
                                    </label>
                                </div>
                                <div>
                                    <input type="radio" name="visibility" id="visibility_role"
                                           value="role" class="form-radio h-4 w-4 text-indigo-600"
                                           {{ old('visibility', $announcement->visibility) === 'role' ? 'checked' : '' }} required>
                                    <label for="visibility_role" class="ml-2 block text-sm font-medium text-gray-700">
                                        Role Tertentu
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="unit-field" class="mt-2 {{ (old('visibility', $announcement->visibility) !== 'unit' && !old('visibility')) ? 'hidden' : '' }}">
                            <label for="target_unit_kerja" class="block text-sm font-medium text-gray-700">
                                Unit Kerja Tujuan
                            </label>
                            <input type="text" name="target_unit_kerja" id="target_unit_kerja"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                   value="{{ old('target_unit_kerja', $announcement->target_unit_kerja ?: Auth::user()->unit_kerja) }}">
                        </div>

                        <div id="role-field" class="mt-2 {{ (old('visibility', $announcement->visibility) !== 'role' && !old('visibility')) ? 'hidden' : '' }}">
                            <label for="target_role" class="block text-sm font-medium text-gray-700">
                                Role Tujuan
                            </label>
                            <select name="target_role" id="target_role"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">-- Pilih Role --</option>
                                <option value="admin" {{ old('target_role', $announcement->target_role) === 'admin' ? 'selected' : '' }}>Admin</option>
                                <option value="pemilik" {{ old('target_role', $announcement->target_role) === 'pemilik' ? 'selected' : '' }}>Pemilik</option>
                                <option value="atasan" {{ old('target_role', $announcement->target_role) === 'atasan' ? 'selected' : '' }}>Atasan</option>
                                <option value="pengguna" {{ old('target_role', $announcement->target_role) === 'pengguna' ? 'selected' : '' }}>Pengguna</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-3">
                        <a href="{{ route('announcements.show', $announcement) }}"
                           class="px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Batal
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-indigo-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </</div>
    </div>
</x-app-layout>