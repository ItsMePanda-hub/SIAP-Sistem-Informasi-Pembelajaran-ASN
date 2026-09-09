<?php

namespace App\Http\Controllers;

use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamController extends Controller
{
    public function index(StatisticsService $statisticsService)
    {
        $user = Auth::user();

        abort_unless(in_array($user->role, ['admin', 'pemilik', 'atasan']), 403);

        $roster = $statisticsService->getTeamOverviewForUser($user);

        return view('team.index', compact('roster'));
    }
}
