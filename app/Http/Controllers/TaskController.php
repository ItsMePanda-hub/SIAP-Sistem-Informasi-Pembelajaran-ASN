<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = Task::with(['creator', 'assignments.user'])->latest();

        if (in_array($user->role, ['admin', 'pemilik'])) {
            // Melihat semua task
        } elseif ($user->role === 'atasan') {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('target_unit_kerja', $user->unit_kerja)
                  ->orWhereHas('assignments.user', fn ($sub) => $sub->where('unit_kerja', $user->unit_kerja));
            });
        } else {
            $query->whereHas('assignments', fn ($q) => $q->where('user_id', $user->id));
        }

        $tasks = $query->paginate(15);

        return view('tasks.index', compact('tasks'));
    }

    public function create()
    {
        $this->authorize('create', Task::class);

        $user = Auth::user();

        if ($user->role === 'atasan') {
            $users = User::where('role', 'pegawai')
                ->where('unit_kerja', $user->unit_kerja)
                ->orderBy('name')
                ->get();
            $unitKerjaList = collect([$user->unit_kerja]);
        } else {
            $users = User::where('role', 'pegawai')
                ->orderBy('name')
                ->get();
            $unitKerjaList = User::whereNotNull('unit_kerja')->distinct()->pluck('unit_kerja');
        }

        return view('tasks.create', compact('users', 'unitKerjaList'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Task::class);

        $user = Auth::user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'deadline' => 'required|date|after:now',
            'target_type' => 'required|in:bidang,individu',
            'target_unit_kerja' => 'nullable|string|required_if:target_type,bidang',
            'user_ids' => 'nullable|array|required_if:target_type,individu|min:1',
            'user_ids.*' => 'exists:users,id',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,mp4|max:51200',
        ]);

        $targetUnit = $validated['target_unit_kerja'] ?? null;

        if ($user->role === 'atasan') {
            if ($validated['target_type'] === 'bidang') {
                if ($targetUnit && $targetUnit !== $user->unit_kerja) {
                    throw ValidationException::withMessages([
                        'target_unit_kerja' => ['Atasan hanya dapat membuat task untuk bidangnya sendiri.'],
                    ]);
                }
                $targetUnit = $user->unit_kerja;
            } else {
                $foreignUsersCount = User::whereIn('id', $validated['user_ids'])
                    ->where(function ($q) use ($user) {
                        $q->where('unit_kerja', '!=', $user->unit_kerja)
                          ->orWhereNull('unit_kerja');
                    })
                    ->count();

                if ($foreignUsersCount > 0) {
                    throw ValidationException::withMessages([
                        'user_ids' => ['Hanya dapat menugaskan pegawai dari unit kerja yang sama.'],
                    ]);
                }
            }
        }

        $attachmentPath = $request->hasFile('attachment')
            ? $request->file('attachment')->store('task-attachments', 'public')
            : null;

        $task = Task::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'attachment_path' => $attachmentPath,
            'deadline' => $validated['deadline'],
            'target_type' => $validated['target_type'],
            'target_unit_kerja' => $validated['target_type'] === 'bidang' ? $targetUnit : null,
            'created_by' => $user->id,
        ]);

        if ($validated['target_type'] === 'bidang') {
            $targetPegawai = User::where('role', 'pegawai')
                ->where('unit_kerja', $targetUnit)
                ->pluck('id');

            foreach ($targetPegawai as $userId) {
                TaskAssignment::create([
                    'task_id' => $task->id,
                    'user_id' => $userId,
                    'status' => 'belum_dikerjakan',
                ]);
            }
        } else {
            foreach ($validated['user_ids'] as $userId) {
                TaskAssignment::create([
                    'task_id' => $task->id,
                    'user_id' => $userId,
                    'status' => 'belum_dikerjakan',
                ]);
            }
        }

        return redirect()->route('tasks.index')->with('status', 'Task berhasil dibuat di Workspace.');
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);

        $user = Auth::user();

        $task->load(['creator', 'assignments.user', 'assignments.reviewer']);

        $myAssignment = null;
        if ($user->role === 'pegawai') {
            $myAssignment = $task->assignments->firstWhere('user_id', $user->id);
        }

        return view('tasks.show', compact('task', 'myAssignment'));
    }

    public function submitWork(Request $request, TaskAssignment $assignment)
    {
        $this->authorize('submit', $assignment);

        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,mp4,jpg,jpeg,png|max:51200',
            'note' => 'nullable|string',
        ]);

        $path = $request->file('file')->store('task-submissions', 'public');

        $assignment->update([
            'submission_path' => $path,
            'submission_note' => $request->note,
            'submitted_at' => now(),
            'is_late' => now()->gt($assignment->task->deadline),
            'status' => 'menunggu_review',
        ]);

        return redirect()->route('tasks.show', $assignment->task)->with('status', 'Hasil pekerjaan berhasil dikumpulkan.');
    }

    public function review(Request $request, TaskAssignment $assignment)
    {
        $this->authorize('review', $assignment);

        $validated = $request->validate([
            'decision' => 'required|in:selesai,revisi',
            'feedback' => 'required_if:decision,revisi|nullable|string',
        ]);

        $assignment->update([
            'status' => $validated['decision'],
            'feedback' => $validated['feedback'] ?? null,
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
        ]);

        return redirect()->route('tasks.show', $assignment->task)->with('status', 'Review pekerjaan berhasil disimpan.');
    }

    public function downloadSubmission(TaskAssignment $assignment)
    {
        $user = Auth::user();

        $isAllowed = false;
        if ($user->role === 'admin') {
            $isAllowed = true;
        } elseif ($user->role === 'atasan' && $user->unit_kerja === $assignment->user->unit_kerja) {
            $isAllowed = true;
        } elseif ($user->id === $assignment->user_id) {
            $isAllowed = true;
        }

        abort_unless($isAllowed, 403);
        abort_if(is_null($assignment->submission_path), 404);

        return response()->download(Storage::disk('public')->path($assignment->submission_path));
    }

    public function downloadAttachment(Task $task)
    {
        $this->authorize('view', $task);

        abort_if(is_null($task->attachment_path), 404);

        return response()->download(Storage::disk('public')->path($task->attachment_path));
    }
}
