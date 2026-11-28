<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Course;
use App\Models\TimetableEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TimetableEntryController extends Controller
{
    private const SEMESTER_CREDIT_LIMIT = 24;

    public function index(Request $request)
    {
        $entries = $request->user()
            ->entries()
            ->select('timetable_entries.*')
            ->with([
                'course' => function ($query) {
                    $query->withCount('students');
                },
            ])
            ->join('courses', 'courses.id', '=', 'timetable_entries.course_id')
            ->orderBy('courses.day')
            ->orderBy('courses.period')
            ->orderBy('courses.code')
            ->get();

        // 申請中・却下済みを除き、承認済み授業だけを単位に数える。
        $totalCredits = $entries
            ->where('status', 'approved')
            ->sum(fn (TimetableEntry $entry): int => $entry->course->credits);
        $creditLimit = self::SEMESTER_CREDIT_LIMIT;
        $isCreditLimitExceeded = $totalCredits > $creditLimit;

        $userId = $request->user()->id;
        $missingRequiredCourses = Course::query()
            ->where('required', true)
            ->whereDoesntHave('timetableEntries', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('status', 'approved');
            })
            ->with([
                'timetableEntries' => function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ])
            ->orderBy('day')
            ->orderBy('period')
            ->orderBy('code')
            ->get();

        $recentAnnouncements = Announcement::query()
            ->whereIn(
                'course_id',
                $request->user()->entries()
                    ->where('status', 'approved')
                    ->select('course_id')
            )
            ->where('created_at', '>=', now()->subDay())
            ->with('course')
            ->latest()
            ->get();

        return view('entries.index', [
            'entries' => $entries,
            'recentAnnouncements' => $recentAnnouncements,
            'totalCredits' => $totalCredits,
            'creditLimit' => $creditLimit,
            'isCreditLimitExceeded' => $isCreditLimitExceeded,
            'remainingCredits' => max(0, $creditLimit - $totalCredits),
            'excessCredits' => max(0, $totalCredits - $creditLimit),
            'missingRequiredCourses' => $missingRequiredCourses,
        ]);
    }

    public function create()
    {
        $courses = Course::query()
            ->orderBy('day')
            ->orderBy('period')
            ->orderBy('code')
            ->get();

        return view('entries.create', [
            'courses' => $courses,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => [
                'required',
                'integer',
                Rule::exists('courses', 'id'),
            ],
        ], [
            'course_id.required' => '授業を一覧から選んでください',
            'course_id.integer' => '授業を一覧から選んでください',
            'course_id.exists' => '授業を一覧から選んでください',
        ]);

        $course = Course::findOrFail($validated['course_id']);

        if ($request->user()->entries()->where('course_id', $course->id)->exists()) {
            throw ValidationException::withMessages([
                'course_id' => 'この授業はすでに申請済み、または履修履歴があります。',
            ]);
        }

        $sameTimeEntryExists = $request->user()
            ->entries()
            ->whereIn('status', ['pending', 'approved'])
            ->whereHas('course', function ($query) use ($course) {
                $query->where('day', $course->day)
                    ->where('period', $course->period);
            })
            ->exists();

        if ($sameTimeEntryExists) {
            throw ValidationException::withMessages([
                'course_id' => 'その曜日・時限にはすでに授業が申請・登録されています。',
            ]);
        }

        $request->user()->entries()->create([
            'course_id' => $course->id,
            'status' => 'pending',
        ]);

        return redirect('/entries')
            ->with('success', '履修を申請しました。');
    }

    public function destroy(Request $request, int $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $entry = TimetableEntry::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $entry->user_id !== (int) $request->user()->id) {
                abort(403, '他人の申請は取り消せません。');
            }

            if ($entry->status !== 'pending') {
                return back()->withErrors([
                    'entry' => '申請中の授業だけ取り消せます。',
                ]);
            }

            $entry->delete();

            return redirect('/entries')
                ->with('success', '申請を取り消しました。');
        });
    }
}
