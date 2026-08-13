<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display font-semibold text-xl text-gray-800">Daftar Akun SIAP</h1>
        <p class="text-sm text-gray-500 mt-1">Untuk pegawai Diskominfo Sawahlunto</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="name" value="Nama" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Konfirmasi Password" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <p class="text-xs text-gray-400">
            Setelah mendaftar, akunmu akan berstatus "pegawai" secara default. Admin akan menetapkan bidang dan peran (atasan/pemilik) sesuai kebutuhan lewat menu Kelola Pengguna.
        </p>

        <x-primary-button class="w-full justify-center py-2.5">
            Daftar
        </x-primary-button>

        <p class="text-center text-sm text-gray-500">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="text-primary font-medium hover:text-primary-dark">Masuk</a>
        </p>
    </form>
</x-guest-layout>
