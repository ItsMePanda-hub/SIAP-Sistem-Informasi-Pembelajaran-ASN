<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function index()
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $logs = ActivityLog::with('user')->latest('id')->paginate(20);

        return view('activity-log.index', compact('logs'));
    }
}
