<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = Announcement::with('creator')->latest();

        if (! $user->isSystemWide()) {
            $query->where(function ($q) use ($user) {
                $q->where('target_unit_kerja', $user->unit_kerja)
                  ->orWhereNull('target_unit_kerja');
            });
        }

        $announcements = $query->paginate(10);

        return view('announcements.index', compact('announcements'));
    }

    public function show(Announcement $announcement)
    {
        $user = Auth::user();

        abort_unless(
            $user->isSystemWide()
                || $announcement->target_unit_kerja === null
                || $announcement->target_unit_kerja === $user->unit_kerja,
            403
        );

        if ($user->role !== 'admin') {
            AnnouncementRead::updateOrCreate(
                ['announcement_id' => $announcement->id, 'user_id' => $user->id],
                ['read_at' => now()]
            );
        }

        $readers = $announcement->reads()
            ->whereHas('user', fn ($q) => $q->where('role', 'pegawai'))
            ->with('user')
            ->get();

        return view('announcements.show', compact('announcement', 'readers'));
    }

    public function create()
    {
        $this->authorizeManage();

        $unitKerjaList = User::whereNotNull('unit_kerja')->distinct()->pluck('unit_kerja');

        return view('announcements.create', compact('unitKerjaList'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $this->authorizeManage();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'category' => 'required|in:mendesak,rutin',
            'target_unit_kerja' => 'nullable|string',
        ]);

        $target = $user->role === 'atasan' ? $user->unit_kerja : ($validated['target_unit_kerja'] ?? null);

        Announcement::create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'category' => $validated['category'],
            'target_unit_kerja' => $target,
            'created_by' => $user->id,
        ]);

        return redirect()->route('announcements.index')->with('status', 'Pengumuman berhasil dikirim.');
    }

    private function authorizeManage(): void
    {
        abort_unless(in_array(Auth::user()->role, ['atasan', 'pemilik', 'admin']), 403);
    }
}
