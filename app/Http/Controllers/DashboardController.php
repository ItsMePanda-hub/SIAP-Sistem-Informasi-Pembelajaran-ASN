<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Services\StatisticsService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected StatisticsService $statisticsService;

    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    public function index()
    {
        $user = Auth::user();

        $announcements = Announcement::latest()->take(5)->get();
        $stats = $this->statisticsService->getStatsForUser($user);

        return view('dashboard', [
            'announcements' => $announcements,
            'stats' => $stats,
        ]);
    }
}
