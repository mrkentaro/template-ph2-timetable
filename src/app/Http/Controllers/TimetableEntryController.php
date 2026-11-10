<?php

namespace App\Http\Controllers;

use App\Models\TimetableEntry;
use Illuminate\Http\Request;

class TimetableEntryController extends Controller
{
    public function index()
    {
        // 曜日順・時限順に授業を取得
        $entries = TimetableEntry::orderBy('day')
            ->orderBy('period')
            ->get();

        // 取得した授業をBladeへ渡す
        return view('entries.index', [
            'entries' => $entries
        ]);
    }

    public function create()
    {
        // 授業の新規登録フォームを表示
        return view('entries.create');
    }

    public function store(Request $request)
    {
        // 入力値を検証し、登録に使う値だけを取得
        $validated = $request->validate([
            'day' => ['required', 'integer', 'between:1,5'],
            'period' => ['required', 'integer', 'between:1,6'],
            'title' => ['required', 'string', 'max:255'],
            'room' => ['nullable', 'string', 'max:255'],
        ]);

        TimetableEntry::create($validated);

        return redirect('/entries');
    }
}
