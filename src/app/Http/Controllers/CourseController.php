<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));

        $courses = Course::query()
            ->with('timetableEntries')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($builder) use ($keyword) {
                    $builder->where('name', 'ilike', "%{$keyword}%")
                        ->orWhere('teacher', 'ilike', "%{$keyword}%");
                });
            })
            ->orderBy('day')
            ->orderBy('period')
            ->orderBy('code')
            ->get();

        $registeredIds = $request->user()?->entries()
            ->pluck('course_id')
            ->all() ?? [];

        return view('courses.index', [
            'courses' => $courses,
            'registeredIds' => $registeredIds,
        ]);
    }

    public function show(Course $course)
    {
        $reviews = $course->reviews()
            ->with('user')
            ->latest()
            ->get();

        return view('courses.show', [
            'course' => $course,
            'reviews' => $reviews,
        ]);
    }
}
