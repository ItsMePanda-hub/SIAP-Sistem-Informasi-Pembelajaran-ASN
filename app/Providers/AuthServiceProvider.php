<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Announcement::class => \App\Policies\AnnouncementPolicy::class,
        \App\Models\Exam::class => \App\Policies\ExamPolicy::class,
        \App\Models\Training::class => \App\Policies\TrainingPolicy::class,
        \App\Models\Letter::class => \App\Policies\LetterPolicy::class,
        \App\Models\Task::class => \App\Policies\TaskPolicy::class,
        \App\Models\TaskAssignment::class => \App\Policies\TaskPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
