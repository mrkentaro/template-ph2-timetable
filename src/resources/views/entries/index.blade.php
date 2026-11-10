<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>自分の時間割</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 40px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            max-width: 800px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #eaf3ff;
        }
    </style>
</head>
<body>
    <h1>自分の時間割</h1>

    <table>
        <thead>
            <tr>
                <th>曜日</th>
                <th>時限</th>
                <th>授業名</th>
                <th>教室</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($entries as $entry)
                <tr>
                    <td>
                        @switch($entry->day)
                            @case(1) 月曜日 @break
                            @case(2) 火曜日 @break
                            @case(3) 水曜日 @break
                            @case(4) 木曜日 @break
                            @case(5) 金曜日 @break
                            @default 不明
                        @endswitch
                    </td>
                    <td>{{ $entry->period }}限</td>
                    <td>{{ $entry->title }}</td>
                    <td>{{ $entry->room ?? '未定' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">
                        まだ授業が登録されていません
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
