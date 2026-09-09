<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        abort_unless(in_array(Auth::user()->role, ['admin', 'pemilik']), 403);

        $users = User::orderBy('name')->get();

        return view('users.index', compact('users'));
    }

    public function edit(User $user)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $atasanList = User::where('role', 'atasan')->get();

        return view('users.edit', compact('user', 'atasanList'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $validated = $request->validate([
            'role' => 'required|in:pegawai,atasan,pemilik,admin',
            'unit_kerja' => 'nullable|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'nip' => 'nullable|string|max:50',
            'atasan_id' => 'nullable|exists:users,id',
        ]);

        if ($validated['role'] !== 'pegawai') {
            $validated['atasan_id'] = null;
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('status', 'Data pengguna berhasil diperbarui.');
    }
}
