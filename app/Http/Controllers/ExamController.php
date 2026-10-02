<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Training;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExamController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = Exam::with('creator')->latest();
        if (! $user->isSystemWide()) {
            $query->where(function ($q) use ($user) {
                $q->where('target_unit_kerja', $user->unit_kerja)
                  ->orWhereNull('target_unit_kerja');
            });
        }

        $exams = $query->get();

        return view('exams.index', compact('exams'));
    }

    public function show(Exam $exam)
    {
        $user = Auth::user();
        $this->authorizeAccess($exam, $user);

        $attempt = $exam->attemptFor($user);

        return view('exams.show', compact('exam', 'attempt'));
    }

    public function start(Exam $exam)
    {
        $user = Auth::user();
        $this->authorizeAccess($exam, $user);

        $attempt = $exam->attemptFor($user);

        if (! $attempt) {
            $attempt = ExamAttempt::create([
                'exam_id' => $exam->id,
                'user_id' => $user->id,
                'status' => 'sedang_berjalan',
                'started_at' => now(),
            ]);
        }

        if ($attempt->status !== 'sedang_berjalan') {
            return redirect()->route('exams.show', $exam)->with('status', 'Ujian ini sudah pernah dikerjakan.');
        }

        return redirect()->route('exams.take', $exam);
    }

    public function take(Exam $exam)
    {
        $user = Auth::user();
        $attempt = $exam->attemptFor($user);

        abort_unless($attempt && $attempt->status === 'sedang_berjalan', 403, 'Tidak ada ujian yang sedang berjalan.');

        $exam->load('questions.options');
        $existingAnswers = ExamAnswer::where('exam_attempt_id', $attempt->id)->get()->keyBy('exam_question_id');

        return view('exams.take', compact('exam', 'attempt', 'existingAnswers'));
    }

    public function saveAnswer(Request $request, Exam $exam)
    {
        $user = Auth::user();
        $attempt = $exam->attemptFor($user);

        abort_unless($attempt && $attempt->status === 'sedang_berjalan', 403);

        $validated = $request->validate([
            'exam_question_id' => 'required|exists:exam_questions,id',
            'exam_option_id' => 'nullable|exists:exam_options,id',
            'essay_answer' => 'nullable|string',
        ]);

        ExamAnswer::updateOrCreate(
            ['exam_attempt_id' => $attempt->id, 'exam_question_id' => $validated['exam_question_id']],
            ['exam_option_id' => $validated['exam_option_id'] ?? null, 'essay_answer' => $validated['essay_answer'] ?? null]
        );

        return response()->json(['saved' => true]);
    }

    public function reportViolation(Exam $exam)
    {
        $user = Auth::user();
        $attempt = $exam->attemptFor($user);

        abort_unless($attempt && $attempt->status === 'sedang_berjalan', 403);

        $attempt->increment('violation_count');

        $isFinal = $attempt->violation_count >= $exam->max_violations;

        if ($isFinal) {
            $this->finishAttempt($attempt, 'selesai_pelanggaran');
        }

        return response()->json([
            'violation_count' => $attempt->violation_count,
            'max_violations' => $exam->max_violations,
            'is_final' => $isFinal,
        ]);
    }

    public function submit(Exam $exam)
    {
        $user = Auth::user();
        $attempt = $exam->attemptFor($user);

        abort_unless($attempt && $attempt->status === 'sedang_berjalan', 403);

        $this->finishAttempt($attempt, 'selesai');

        return redirect()->route('exams.show', $exam)->with('status', 'Ujian berhasil dikumpulkan.');
    }

    private function finishAttempt(ExamAttempt $attempt, string $status = null): void
    {
        $exam = $attempt->exam;
        $pgQuestions = $exam->questions()->where('type', 'pilihan_ganda')->get();
        $essayQuestions = $exam->questions()->where('type', 'esai')->get();
        
        $totalQuestions = $pgQuestions->count() + $essayQuestions->count();

        $benar = 0;
        foreach ($pgQuestions as $q) {
            $answer = ExamAnswer::where('exam_attempt_id', $attempt->id)
                ->where('exam_question_id', $q->id)
                ->first();

            if ($answer && $answer->option && $answer->option->is_correct) {
                $benar++;
            }
        }

        $ungradedEssays = 0;
        foreach ($essayQuestions as $q) {
            $answer = ExamAnswer::where('exam_attempt_id', $attempt->id)
                ->where('exam_question_id', $q->id)
                ->first();

            if ($answer && $answer->essay_graded_correct !== null) {
                if ($answer->essay_graded_correct) {
                    $benar++;
                }
            } else {
                $ungradedEssays++;
            }
        }

        $score = $totalQuestions > 0 ? round(($benar / $totalQuestions) * 100, 2) : null;

        if ($ungradedEssays > 0) {
            $finalStatus = 'menunggu_penilaian_esai';
        } else {
            if ($status) {
                $finalStatus = $status;
            } else {
                $finalStatus = $attempt->violation_count >= $exam->max_violations ? 'selesai_pelanggaran' : 'selesai';
            }
        }

        $previousStatus = $attempt->status;
        $previousScore = $attempt->score;

        $attempt->update([
            'status' => $finalStatus,
            'score' => $score,
            'submitted_at' => $attempt->submitted_at ?? now(),
        ]);

        $isFinalResult = ! is_null($score) && $finalStatus !== 'menunggu_penilaian_esai' && $finalStatus !== 'sedang_berjalan';
        $wasAlreadyFinal = ! is_null($previousScore) && ! in_array($previousStatus, ['sedang_berjalan', 'menunggu_penilaian_esai'], true);
        if ($isFinalResult && ! $wasAlreadyFinal) {
            $attempt->load('exam');
            \Illuminate\Support\Facades\Notification::send($attempt->user, new \App\Notifications\ExamResultAvailable($attempt));
        }
    }

    private function authorizeAccess(Exam $exam, $user): void
    {
        $this->authorize('view', $exam);
    }

    public function create()
    {
        $this->authorize('create', Exam::class);

        $trainings = Training::all();
        $unitKerjaList = User::whereNotNull('unit_kerja')->distinct()->pluck('unit_kerja');

        return view('exams.create', compact('trainings', 'unitKerjaList'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $this->authorize('create', Exam::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'training_id' => 'nullable|exists:trainings,id',
            'target_unit_kerja' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:1',
            'max_violations' => 'required|integer|min:1|max:10',
            'questions' => 'required|array|min:1',
            'questions.*.type' => 'required|in:pilihan_ganda,esai',
            'questions.*.question' => 'required|string',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*.text' => 'nullable|string',
            'questions.*.correct_index' => 'nullable|integer',
        ]);

        $target = $user->role === 'atasan' ? $user->unit_kerja : ($validated['target_unit_kerja'] ?? null);

        $exam = Exam::create([
            'training_id' => $validated['training_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'target_unit_kerja' => $target,
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'max_violations' => $validated['max_violations'],
            'created_by' => $user->id,
        ]);

        foreach ($validated['questions'] as $i => $q) {
            $question = $exam->questions()->create([
                'type' => $q['type'],
                'question' => $q['question'],
                'order' => $i,
            ]);

            if ($q['type'] === 'pilihan_ganda' && ! empty($q['options'])) {
                foreach ($q['options'] as $j => $opt) {
                    if (empty($opt['text'])) {
                        continue;
                    }
                    $question->options()->create([
                        'option_text' => $opt['text'],
                        'is_correct' => (int) ($q['correct_index'] ?? -1) === $j,
                    ]);
                }
            }
        }

        $examRecipients = is_null($exam->target_unit_kerja)
            ? User::all()->reject(fn ($u) => $u->id === $user->id)
            : User::where('unit_kerja', $exam->target_unit_kerja)->where('id', '!=', $user->id)->get();
        if ($examRecipients->isNotEmpty()) {
            \Illuminate\Support\Facades\Notification::send($examRecipients, new \App\Notifications\ExamAvailable($exam));
        }

        return redirect()->route('exams.index')->with('status', 'Ujian berhasil dibuat.');
    }

    public function results(Exam $exam)
    {
        $user = Auth::user();
        $this->authorize('create', Exam::class);
        $this->authorizeAccess($exam, $user);

        $attempts = $exam->attempts()->with(['user', 'answers.question', 'answers.option'])->get();

        return view('exams.results', compact('exam', 'attempts'));
    }

    public function gradeEssay(Request $request, ExamAnswer $answer)
    {
        $user = Auth::user();
        $this->authorize('create', Exam::class);

        $validated = $request->validate([
            'is_correct' => 'required|boolean',
        ]);

        $answer->update(['essay_graded_correct' => $validated['is_correct']]);

        $this->finishAttempt($answer->attempt);

        return back()->with('status', 'Penilaian esai tersimpan.');
    }

    public function appealViolation(Request $request, Exam $exam)
    {
        $user = Auth::user();
        $attempt = $exam->attemptFor($user);

        abort_unless($attempt && $attempt->status === 'selesai_pelanggaran' && $attempt->violation_appeal_status === null, 403);

        $validated = $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $attempt->update([
            'violation_appeal_status' => 'diajukan',
            'violation_appeal_note' => $validated['note'],
        ]);

        return back()->with('status', 'Banding pelanggaran berhasil diajukan.');
    }

    public function resolveAppeal(Request $request, ExamAttempt $attempt)
    {
        $user = Auth::user();
        $this->authorize('create', Exam::class);
        $this->authorizeAccess($attempt->exam, $user);

        $validated = $request->validate([
            'action' => 'required|in:terima,tolak',
        ]);

        if ($validated['action'] === 'terima') {
            $attempt->update([
                'violation_appeal_status' => 'diterima',
                'status' => 'diganti_banding',
            ]);
            \App\Models\ExamAttempt::create([
                'exam_id' => $attempt->exam_id,
                'user_id' => $attempt->user_id,
                'status' => 'sedang_berjalan',
                'started_at' => now(),
                'score' => null,
                'violation_count' => 0,
            ]);
            $statusMsg = 'Banding diterima. Peserta dapat mengulang ujian.';
        } else {
            $attempt->update([
                'violation_appeal_status' => 'ditolak',
            ]);
            $statusMsg = 'Banding ditolak.';
        }

        return back()->with('status', $statusMsg);
    }
}
