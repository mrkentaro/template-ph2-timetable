<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TimetableEntryController;

Route::get('/', function () {
    return view('welcome');
});

// Week3：授業一覧
Route::get('/timetable', [
    TimetableController::class,
    'index'
]);

// Week4：自分の時間割一覧
Route::get('/entries', [
    TimetableEntryController::class,
    'index'
]);

// Week5：授業の新規登録フォーム
Route::get('/entries/create', [
    TimetableEntryController::class,
    'create'
]);

// Week5：授業の新規登録
Route::post('/entries', [
    TimetableEntryController::class,
    'store'
]);
