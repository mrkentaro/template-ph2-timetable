<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TimetableEntryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminEntryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/timetable', [TimetableController::class, 'index']);


Route::middleware('auth')->group(function () {
    // 自分の時間割
    Route::get('/entries', [TimetableEntryController::class, 'index']);

    // 履修申請画面
    Route::get('/entries/create', [TimetableEntryController::class, 'create']);

    // 履修申請
    Route::post('/entries', [TimetableEntryController::class, 'store']);

    // 申請取り消し
    Route::delete('/entries/{id}', [TimetableEntryController::class, 'destroy']);
});


Route::get('/courses', [CourseController::class, 'index']);
// 授業詳細
Route::get('/courses/{course}', [
    CourseController::class,
    'show'
]);

// 気になる授業の登録・解除（ログイン必須）
Route::middleware('auth')->group(function () {
    Route::post('/courses/{course}/favorite', [
        CourseController::class,
        'favoriteStore'
    ]);

    Route::delete('/courses/{course}/favorite', [
        CourseController::class,
        'favoriteDestroy'
    ]);
});

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

/*
|--------------------------------------------------------------------------
| ログイン機能
|--------------------------------------------------------------------------
*/

// ログイン画面
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

// ログイン処理
Route::post('/login', function (Request $request) {

    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();

        return redirect()->intended('/entries');
    }

    return back()
        ->withErrors([
            'email' => 'メールアドレスまたはパスワードが正しくありません。',
        ])
        ->onlyInput('email');
});

