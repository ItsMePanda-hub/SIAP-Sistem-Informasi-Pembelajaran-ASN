<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LetterController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = Letter::with('uploader')->latest();

        if (! $user->isSystemWide()) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('visibility')
                  ->whereNull('target_unit_kerja')
                  ->orWhere('visibility', 'all')
                  ->orWhere(function ($q) use ($user) {
                      $q->where('visibility', 'unit')
                        ->where('target_unit_kerja', $user->unit_kerja);
                  })
                  ->orWhere(function ($q) use ($user) {
                      $q->where('visibility', 'role')
                        ->where('target_role', $user->role);
                  })
                  ->orWhere(function ($q) use ($user) {
                      // Fallback for old records without visibility
                      $q->whereNull('visibility')
                        ->where('target_unit_kerja', $user->unit_kerja);
                  });
            });
        }

        $letters = $query->paginate(10);
        $unitKerjaList = User::whereNotNull('unit_kerja')->distinct()->pluck('unit_kerja');

        return view('letters.index', compact('letters', 'unitKerjaList'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $this->authorize('create', Letter::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'nomor_surat' => 'nullable|string|max:100',
            'category' => 'required|in:sk,nota_dinas,surat_tugas,lainnya',
            'file' => 'required|file|mimes:pdf|max:10240',
            'visibility' => 'required|in:all,unit,role',
            'target_unit_kerja' => 'nullable|string',
            'target_role' => 'nullable|in:admin,pemilik,atasan,pengguna',
        ]);

        if ($validated['visibility'] === 'unit' && empty($validated['target_unit_kerja'])) {
            $validated['target_unit_kerja'] = $user->unit_kerja;
        }

        if ($validated['visibility'] === 'role' && empty($validated['target_role'])) {
            $validated['target_role'] = $user->role;
        }

        $target_unit_kerja = $user->role === 'atasan' && $validated['visibility'] === 'unit' ? $user->unit_kerja : ($validated['target_unit_kerja'] ?? null);

        $path = $request->file('file')->store('letters', 'public');

        Letter::create([
            'title' => $validated['title'],
            'nomor_surat' => $validated['nomor_surat'] ?? null,
            'category' => $validated['category'],
            'visibility' => $validated['visibility'],
            'target_unit_kerja' => $target_unit_kerja,
            'target_role' => $validated['target_role'] ?? null,
            'file_path' => $path,
            'uploaded_by' => $user->id,
        ]);

        return redirect()->route('letters.index')->with('status', 'Surat berhasil diunggah.');
    }

    public function download(Letter $letter)
    {
        $user = Auth::user();

        abort_unless($letter->isAccessibleBy($user), 403);

        return response()->download(storage_path('app/public/' . $letter->file_path), $letter->title . '.pdf');
    }
}
