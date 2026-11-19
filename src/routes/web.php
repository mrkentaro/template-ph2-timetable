<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TimetableEntryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminEntryController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/timetable', [TimetableController::class, 'index']);

Route::get('/entries', [TimetableEntryController::class, 'index']);
Route::get('/entries/create', [TimetableEntryController::class, 'create']);
Route::post('/entries', [TimetableEntryController::class, 'store']);
Route::delete('/entries/{id}', [TimetableEntryController::class, 'destroy']);

Route::get('/courses', [CourseController::class, 'index']);
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
