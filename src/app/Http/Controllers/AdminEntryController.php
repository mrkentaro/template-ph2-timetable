<?php

namespace App\Http\Controllers;

use App\Models\TimetableEntry;
use Illuminate\Support\Facades\DB;

class AdminEntryController extends Controller
{
    // 申請中の一覧
    public function index()
    {
        $entries = TimetableEntry::with([
            'user',
            'course' => function ($query) {
                $query->withCount('approvedEntries');
            }
        ])
        ->where('status', 'pending')
        ->orderBy('id')
        ->get();

        return view('admin.entries.index', [
            'entries' => $entries
        ]);
    }

    // 承認
    public function approve(TimetableEntry $entry)
    {
        return DB::transaction(function () use ($entry) {

            // ① 対象授業をロックする
            $course = DB::table('courses')
                ->where('id', $entry->course_id)
                ->lockForUpdate()
                ->first();

            if (!$course) {
                abort(404, '授業が見つかりません。');
            }

            // ② 申請の最新状態を取得してロックする
            $latestEntry = TimetableEntry::whereKey($entry->id)
                ->lockForUpdate()
                ->firstOrFail();

            // ③ pending以外は承認できない
            if ($latestEntry->status !== 'pending') {
                return back()->withErrors([
                    'entry' => '申請中の授業だけ承認できます。'
                ]);
            }

            // ④ この授業の承認済み人数を数える
            $approvedCount = TimetableEntry::where(
                'course_id',
                $course->id
            )
            ->where('status', 'approved')
            ->count();

            // ⑤ 定員に達していたら承認しない
            if ($approvedCount >= $course->capacity) {
                return back()->withErrors([
                    'entry' => '定員に達しているため承認できません。'
                ]);
            }

            // ⑥ 申請を承認する
            $latestEntry->status = 'approved';
            $latestEntry->decided_at = now();
            $latestEntry->save();

            return back()->with('success', '承認しました。');
        });
    }

    // 却下
    public function reject(TimetableEntry $entry)
    {
        return DB::transaction(function () use ($entry) {

            // ① 申請の最新状態を取得してロックする
            $latestEntry = TimetableEntry::whereKey($entry->id)
                ->lockForUpdate()
                ->firstOrFail();

            // ② pending以外は却下できない
            if ($latestEntry->status !== 'pending') {
                return back()->withErrors([
                    'entry' => '申請中の授業だけ却下できます。'
                ]);
            }

            // ③ 却下状態と決定日時を保存する
            $latestEntry->status = 'rejected';
            $latestEntry->decided_at = now();
            $latestEntry->save();

            return back()->with('success', '却下しました。');
        });
    }
}
