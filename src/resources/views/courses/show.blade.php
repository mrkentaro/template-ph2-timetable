<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $course->name }} | 授業詳細</title>

    <style>
        body {
            font-family: sans-serif;
            color: #1f2937;
            background: #f9fafb;
            margin: 40px;
            line-height: 1.7;
        }

        main {
            max-width: 800px;
            margin: 0 auto;
        }

        section {
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 24px;
            margin-top: 24px;
        }

        input, textarea {
            display: block;
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            margin: 8px 0 16px;
            border: 1px solid #d1d5db;
            border-radius: 5px;
        }

        button {
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            cursor: pointer;
        }

        .announcement {
            padding: 16px 0;
            border-bottom: 1px solid #d1d5db;
        }

        .error {
            color: #dc2626;
        }

        .success {
            color: #166534;
        }

        .debug {
            background: #fff3cd;
            padding: 16px;
            margin-top: 24px;
            border-radius: 8px;
        }
    </style>
</head>

<body>
<main>

    <!-- 授業情報 -->
    <a href="/courses">← 授業一覧へ戻る</a>

    <h1>{{ $course->name }}</h1>

    <p>科目コード：{{ $course->code }}</p>

    <!-- 投稿成功メッセージ -->
    @if(session('success'))
        <p class="success">
            {{ session('success') }}
        </p>
    @endif

    <!-- ログイン状態の確認（デバッグ用） -->
    <div class="debug">
        <h3>ログイン状態の確認</h3>

        <p>
            ログイン状態：
            {{ auth()->check() ? 'ログイン済み' : '未ログイン' }}
        </p>

        @auth
            <p>
                ユーザーID：
                {{ auth()->id() }}
            </p>

            <p>
                管理者権限：
                {{ auth()->user()->is_admin ? 'あり' : 'なし' }}
            </p>
        @endauth
    </div>

    <!-- 管理者専用：お知らせ投稿フォーム -->
    @auth
        @if(auth()->user()->is_admin)
            <section>
                <h2>お知らせを投稿する</h2>

                <form
                    method="POST"
                    action="/courses/{{ $course->id }}/announcements"
                >
                    @csrf

                    <label for="title">
                        タイトル（100文字以内）
                    </label>

                    <input
                        type="text"
                        id="title"
                        name="title"
                        maxlength="100"
                        value="{{ old('title') }}"
                        required
                    >

                    @error('title')
                        <p class="error">
                            {{ $message }}
                        </p>
                    @enderror

                    <label for="body">
                        本文（100文字以内）
                    </label>

                    <textarea
                        id="body"
                        name="body"
                        maxlength="100"
                        rows="5"
                        required
                    >{{ old('body') }}</textarea>

                    @error('body')
                        <p class="error">
                            {{ $message }}
                        </p>
                    @enderror

                    <button type="submit">
                        お知らせを投稿
                    </button>
                </form>
            </section>
        @endif
    @endauth

    <!-- 最近のお知らせ -->
    <section>
        <h2>最近のお知らせ（過去24時間）</h2>

        @forelse($announcements as $announcement)
            <div class="announcement">

                <h3>{{ $announcement->title }}</h3>

                <p style="white-space: pre-line;">
                    {{ $announcement->body }}
                </p>

                <small>
                    投稿日時：
                    {{ $announcement->created_at->format('Y/m/d H:i') }}
                </small>

            </div>
        @empty
            <p>最近のお知らせはありません。</p>
        @endforelse
    </section>

</main>
</body>
</html>