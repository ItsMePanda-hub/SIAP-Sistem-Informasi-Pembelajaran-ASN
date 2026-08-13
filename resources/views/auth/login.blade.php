<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display font-semibold text-xl text-gray-800">Masuk ke SIAP</h1>
        <p class="text-sm text-gray-500 mt-1">Sistem Informasi &amp; Pembelajaran ASN Diskominfo Sawahlunto</p>
    </div>

    @if (session('status'))
        <div class="mb-4 p-3 bg-primary-light text-primary-dark rounded-lg text-sm">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <x-checkbox id="remember_me" name="remember" />
                <span class="ms-2 text-sm text-gray-500">Ingat saya</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-primary hover:text-primary-dark" href="{{ route('password.request') }}">
                    Lupa password?
                </a>
            @endif
        </div>

        <x-primary-button class="w-full justify-center py-2.5">
            Masuk
        </x-primary-button>

        @if (Route::has('register'))
            <p class="text-center text-sm text-gray-500">
                Belum punya akun?
                <a href="{{ route('register') }}" class="text-primary font-medium hover:text-primary-dark">Daftar</a>
            </p>
        @endif
    </form>
</x-guest-layout>
