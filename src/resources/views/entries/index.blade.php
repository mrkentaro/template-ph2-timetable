<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>自分の時間割</title>
    <style>
        body { color: #1f2937; font-family: sans-serif; margin: 40px; }
        main { max-width: 1000px; }
        nav { display: flex; gap: 16px; margin-bottom: 28px; }
        .user-bar { align-items: center; display: flex; justify-content: space-between; margin-bottom: 24px; }
        .logout-form { display: block; }
        .logout-button { background: #4b5563; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #d1d5db; padding: 12px; text-align: left; }
        th { background: #eff6ff; }
        form { display: inline; }
        button { background: #dc2626; border: 0; border-radius: 4px; color: white; cursor: pointer; padding: 8px 14px; }
        .actions { margin: 20px 0; }
        .notice { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; padding: 12px; }
        .credit-summary { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 24px; padding: 20px; }
        .credit-total { font-size: 24px; font-weight: bold; margin: 8px 0; }
        .credit-status { border-radius: 999px; display: inline-block; font-weight: bold; padding: 6px 12px; }
        .credit-status-within { background: #dcfce7; color: #166534; }
        .credit-status-exceeded { background: #fee2e2; color: #b91c1c; }
        .required-courses { margin-bottom: 28px; }
        .enrollment-status { font-weight: bold; }
    </style>
</head>
<body>
    <main>
        <nav>
            <a href="{{ url('/entries') }}">自分の時間割</a>
            <a href="{{ url('/courses') }}">授業一覧</a>
        </nav>

        <div class="user-bar">
            <h1>{{ auth()->user()->name }}さんの時間割</h1>
            <form class="logout-form" action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="logout-button" type="submit">ログアウト</button>
            </form>
        </div>

        @if (session('success'))
            <p class="notice">{{ session('success') }}</p>
        @endif

        <section class="credit-summary" aria-labelledby="credit-summary-title">
            <h2 id="credit-summary-title">今学期の単位</h2>
            <p class="credit-total">{{ $totalCredits }} / {{ $creditLimit }}単位</p>

            @if ($isCreditLimitExceeded)
                <p class="credit-status credit-status-exceeded">上限超過</p>
                <p>上限を{{ $excessCredits }}単位超えています。</p>
            @else
                <p class="credit-status credit-status-within">上限以内</p>
                <p>あと{{ $remainingCredits }}単位まで履修できます。</p>
            @endif
        </section>

        <section class="required-courses" aria-labelledby="required-courses-title">
            <h2 id="required-courses-title">未履修の必修授業</h2>

            @if ($missingRequiredCourses->isEmpty())
                <p>未履修の必修授業はありません。</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>科目コード</th>
                            <th>授業名</th>
                            <th>単位</th>
                            <th>履修状態</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($missingRequiredCourses as $course)
                            @php($entry = $course->timetableEntries->first())
                            <tr>
                                <td>{{ $course->code }}</td>
                                <td>
                                    <a href="{{ url('/courses/' . $course->id) }}">
                                        {{ $course->name }}
                                    </a>
                                </td>
                                <td>{{ $course->credits }}単位</td>
                                <td class="enrollment-status">
                                    @if ($entry?->status === 'pending')
                                        申請中
                                    @elseif ($entry?->status === 'rejected')
                                        却下済み
                                    @else
                                        未申請
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section>
            <h2>最近のお知らせ（24時間以内）</h2>

            @forelse ($recentAnnouncements as $announcement)
                <article>
                    <h3>
                        <a href="{{ url('/courses/' . $announcement->course_id) }}">
                            {{ $announcement->course->name }}：{{ $announcement->title }}
                        </a>
                    </h3>
                    <p>{{ $announcement->body }}</p>
                    <small>{{ $announcement->created_at->format('Y/m/d H:i') }}</small>
                </article>
            @empty
                <p>最近のお知らせはありません。</p>
            @endforelse
        </section>

        <p class="actions"><a href="{{ url('/entries/create') }}">＋ 授業を登録する</a></p>

        <table>
            <thead>
                <tr>
                    <th>曜日</th>
                    <th>時限</th>
                    <th>授業名</th>
                    <th>単位</th>
                    <th>申請状態</th>
                    <th>担当教員</th>
                    <th>教室</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr>
                        <td>{{ $entry->course->day_name }}</td>
                        <td>{{ $entry->course->period }}限</td>
                        <td>
                            {{ $entry->course->name }}
                            <span>（受講者：{{ $entry->course->students_count }}人）</span>
                        </td>
                        <td>{{ $entry->course->credits }}単位</td>
                        <td>
                            @if ($entry->status === 'approved')
                                承認済み
                            @elseif ($entry->status === 'rejected')
                                却下済み
                            @else
                                申請中
                            @endif
                        </td>
                        <td>{{ $entry->course->teacher ?? '未定' }}</td>
                        <td>{{ $entry->course->room }}</td>
                        <td>
                            <form action="{{ url('/entries/' . $entry->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit">削除</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">まだ授業が登録されていません</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </main>
</body>
</html>
