<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\TrainingProgress;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TrainingController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = Training::with('creator')->latest();

        if (! $user->isSystemWide()) {
            $query->where(function ($q) use ($user) {
                $q->where('target_unit_kerja', $user->unit_kerja)
                  ->orWhereNull('target_unit_kerja');
            });
        }

        $trainings = $query->get();
        $progresses = TrainingProgress::where('user_id', $user->id)->pluck('status', 'training_id');

        return view('trainings.index', compact('trainings', 'progresses'));
    }

    public function show(Training $training)
    {
        $user = Auth::user();

        $this->authorize('view', $training);

        $progress = $training->progressFor($user);

        return view('trainings.show', compact('training', 'progress'));
    }

    public function start(Training $training)
    {
        abort_if(Auth::user()->role === 'admin', 403, 'Admin tidak mengikuti pelatihan sebagai peserta.');

        TrainingProgress::firstOrCreate(
            ['training_id' => $training->id, 'user_id' => Auth::id()],
            ['status' => 'sedang_berjalan']
        );

        return redirect()->route('trainings.show', $training)->with('status', 'Pelatihan dimulai.');
    }

    public function complete(Training $training)
    {
        abort_if(Auth::user()->role === 'admin', 403, 'Admin tidak mengikuti pelatihan sebagai peserta.');

        $progress = TrainingProgress::firstOrCreate(
            ['training_id' => $training->id, 'user_id' => Auth::id()],
            ['status' => 'sedang_berjalan']
        );

        $progress->update([
            'status' => 'selesai',
            'completed_at' => now(),
            'certificate_code' => $progress->certificate_code ?? strtoupper(\Illuminate\Support\Str::random(10)),
        ]);

        return redirect()->route('trainings.show', $training)->with('status', 'Selamat, pelatihan selesai! Sertifikat sudah diterbitkan.');
    }

    public function create()
    {
        $this->authorize('create', Training::class);

        $unitKerjaList = User::whereNotNull('unit_kerja')->distinct()->pluck('unit_kerja');

        return view('trainings.create', compact('unitKerjaList'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $this->authorize('create', Training::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_unit_kerja' => 'nullable|string',
            'materi' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $target = $user->role === 'atasan' ? $user->unit_kerja : ($validated['target_unit_kerja'] ?? null);

        $materialPath = $request->hasFile('materi')
            ? $request->file('materi')->store('training-materials', 'public')
            : null;

        Training::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'target_unit_kerja' => $target,
            'material_path' => $materialPath,
            'created_by' => $user->id,
        ]);

        return redirect()->route('trainings.index')->with('status', 'Pelatihan berhasil dibuat.');
    }

    public function downloadMateri(Training $training)
    {
        $this->authorize('view', $training);

        abort_if(is_null($training->material_path), 404);

        return response()->download(Storage::disk('public')->path($training->material_path));
    }
}
