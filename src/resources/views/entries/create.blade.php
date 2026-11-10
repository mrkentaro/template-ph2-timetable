<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>授業の新規登録</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 40px;
        }
        form {
            max-width: 500px;
        }
        label {
            display: block;
            margin-top: 16px;
        }
        input, select {
            box-sizing: border-box;
            margin-top: 4px;
            padding: 8px;
            width: 100%;
        }
        button {
            margin-top: 24px;
            padding: 10px 20px;
        }
        .errors {
            color: #c00;
        }
    </style>
</head>
<body>
    <h1>授業の新規登録</h1>

    @if ($errors->any())
        <div class="errors">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="/entries" method="POST">
        @csrf

        <label for="day">曜日</label>
        <select id="day" name="day" required>
            <option value="">選択してください</option>
            <option value="1" @selected(old('day') == '1')>月曜日</option>
            <option value="2" @selected(old('day') == '2')>火曜日</option>
            <option value="3" @selected(old('day') == '3')>水曜日</option>
            <option value="4" @selected(old('day') == '4')>木曜日</option>
            <option value="5" @selected(old('day') == '5')>金曜日</option>
        </select>

        <label for="period">時限</label>
        <select id="period" name="period" required>
            <option value="">選択してください</option>
            @for ($period = 1; $period <= 6; $period++)
                <option value="{{ $period }}" @selected(old('period') == $period)>
                    {{ $period }}限
                </option>
            @endfor
        </select>

        <label for="title">授業名</label>
        <input id="title" name="title" type="text" value="{{ old('title') }}" required>

        <label for="room">教室（任意）</label>
        <input id="room" name="room" type="text" value="{{ old('room') }}">

        <button type="submit">登録する</button>
    </form>
</body>
</html>
