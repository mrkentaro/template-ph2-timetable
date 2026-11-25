<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TimetableEntryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminEntryController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReviewController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/timetable', [TimetableController::class, 'index']);

Route::get('/entries', [TimetableEntryController::class, 'index'])
    ->middleware('auth');
Route::get('/entries/create', [TimetableEntryController::class, 'create']);
Route::post('/entries', [TimetableEntryController::class, 'store']);
Route::delete('/entries/{id}', [TimetableEntryController::class, 'destroy']);

Route::get('/courses', [CourseController::class, 'index']);
// Week11：授業詳細ページ
Route::get('/courses/{course}', [
    CourseController::class,
    'show'
]);
// 管理者専用ページ
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->group(function () {

        // 申請中一覧
        Route::get('/entries', [
            AdminEntryController::class,
            'index'
        ])->name('admin.entries.index');

        // 承認
        Route::post('/entries/{entry}/approve', [
            AdminEntryController::class,
            'approve'
        ])->name('admin.entries.approve');

        // 却下
        Route::post('/entries/{entry}/reject', [
            AdminEntryController::class,
            'reject'
        ])->name('admin.entries.reject');
    });
// 学生の授業申請
Route::post('/entries', [
    TimetableEntryController::class,
    'store'
])->middleware('auth');

// 学生の申請取り消し
Route::delete('/entries/{id}', [
    TimetableEntryController::class,
    'destroy'
])->middleware('auth');

// Week11：管理者のみがお知らせを投稿できる
Route::post('/courses/{course}/announcements', [
    AnnouncementController::class,
    'store'
])->middleware(['auth', 'admin']);
// 未ログインユーザー用
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// ログイン済みユーザー用
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
// レビューの投稿・削除（ログイン必須）
Route::middleware('auth')->group(function () {
    Route::post('/courses/{course}/reviews', [
        ReviewController::class, 'store'
    ]);

    Route::delete('/reviews/{review}', [
        ReviewController::class, 'destroy'
    ]);
});