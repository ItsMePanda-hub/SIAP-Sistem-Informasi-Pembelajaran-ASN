<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $announcements = Announcement::latest()->take(5)->get();
        $unreadCount = $announcements->filter(fn ($a) => ! $a->isReadBy($user))->count();

        return view('dashboard', [
            'announcements' => $announcements,
            'unreadCount' => $unreadCount,
        ]);
    }
}
