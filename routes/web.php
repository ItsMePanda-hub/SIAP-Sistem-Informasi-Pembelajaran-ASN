<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Pengumuman
    Route::get('/pengumuman', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/pengumuman/buat', [AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('/pengumuman', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('/pengumuman/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');

    // Surat & Dokumen
    Route::get('/surat', [LetterController::class, 'index'])->name('letters.index');
    Route::post('/surat', [LetterController::class, 'store'])->name('letters.store');
    Route::get('/surat/{letter}/unduh', [LetterController::class, 'download'])->name('letters.download');

    // Pelatihan
    Route::get('/pelatihan', [TrainingController::class, 'index'])->name('trainings.index');
    Route::get('/pelatihan/buat', [TrainingController::class, 'create'])->name('trainings.create');
    Route::post('/pelatihan', [TrainingController::class, 'store'])->name('trainings.store');
    Route::get('/pelatihan/{training}', [TrainingController::class, 'show'])->name('trainings.show');
    Route::post('/pelatihan/{training}/mulai', [TrainingController::class, 'start'])->name('trainings.start');
    Route::post('/pelatihan/{training}/selesai', [TrainingController::class, 'complete'])->name('trainings.complete');

    // Ujian
    Route::get('/ujian', [ExamController::class, 'index'])->name('exams.index');
    Route::get('/ujian/buat', [ExamController::class, 'create'])->name('exams.create');
    Route::post('/ujian', [ExamController::class, 'store'])->name('exams.store');
    Route::get('/ujian/{exam}', [ExamController::class, 'show'])->name('exams.show');
    Route::post('/ujian/{exam}/mulai', [ExamController::class, 'start'])->name('exams.start');
    Route::get('/ujian/{exam}/kerjakan', [ExamController::class, 'take'])->name('exams.take');
    Route::post('/ujian/{exam}/jawab', [ExamController::class, 'saveAnswer'])->name('exams.answer');
    Route::post('/ujian/{exam}/pelanggaran', [ExamController::class, 'reportViolation'])->name('exams.violation');
    Route::post('/ujian/{exam}/kumpulkan', [ExamController::class, 'submit'])->name('exams.submit');
    Route::get('/ujian/{exam}/hasil', [ExamController::class, 'results'])->name('exams.results');
    Route::post('/jawaban-esai/{answer}/nilai', [ExamController::class, 'gradeEssay'])->name('exams.grade-essay');

    // Kelola Pengguna (admin)
    Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
    Route::get('/pengguna/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/pengguna/{user}', [UserController::class, 'update'])->name('users.update');
});

require __DIR__.'/auth.php';
