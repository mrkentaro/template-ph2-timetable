<?php

namespace App\Http\Controllers;

use App\Models\Course;

class CourseController extends Controller
{
    // 授業一覧ページ
    public function index()
    {
        $courses = Course::query()
            ->with('timetableEntries')
            ->orderBy('day')
            ->orderBy('period')
            ->orderBy('code')
            ->get();

        return view('courses.index', [
            'courses' => $courses,
        ]);
    }

    // ★ Week11で追加：授業詳細ページ
    public function show(Course $course)
    {
    $announcements = $course->announcements()
        ->where('created_at', '>=', now()->subDay())
        ->latest()
        ->get();

    return view('courses.show', [
        'course' => $course,
        'announcements' => $announcements,
    ]);
    }
}
