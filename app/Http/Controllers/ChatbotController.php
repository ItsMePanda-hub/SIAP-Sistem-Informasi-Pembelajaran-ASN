<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\StatisticsService;
use App\Services\GeminiService;
use App\Models\Announcement;
use App\Models\Exam;
use App\Models\Training;
use App\Models\Letter;
use Illuminate\Support\Facades\Auth;

class ChatbotController extends Controller
{
    protected $statisticsService;
    protected $geminiService;

    public function __construct(StatisticsService $statisticsService, GeminiService $geminiService)
    {
        $this->statisticsService = $statisticsService;
        $this->geminiService = $geminiService;
    }

    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        $user = Auth::user();
        
        $stats = $this->statisticsService->getStatsForUser($user);

        // Fetch data and filter via Policy (user-scoped)
        $announcements = Announcement::all()->filter(fn($item) => $user->can('view', $item))->values()->toArray();
        $exams = Exam::all()->filter(fn($item) => $user->can('view', $item))->values()->toArray();
        $trainings = Training::all()->filter(fn($item) => $user->can('view', $item))->values()->toArray();
        $letters = Letter::all()->filter(fn($item) => $user->can('view', $item))->values()->toArray();

        $contextData = [
            'user' => $user->only(['name', 'email', 'role', 'unit_kerja']),
            'statistics' => $stats,
            'announcements' => $announcements,
            'exams' => $exams,
            'trainings' => $trainings,
            'letters' => $letters,
        ];

        $systemInstruction = "Kamu asisten SIAP, HANYA jawab soal cara pakai SIAP dan data yang diberikan di context ini. Tolak pertanyaan di luar topik SIAP dengan sopan. JANGAN pernah mengarang data yang tidak ada di context.";

        $answer = $this->geminiService->ask($systemInstruction, $contextData, $request->input('message'));

        return response()->json(['reply' => $answer]);
    }
}
