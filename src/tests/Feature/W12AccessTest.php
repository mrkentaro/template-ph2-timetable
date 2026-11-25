<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\TimetableEntry;

class W12AccessTest extends TestCase
{
    // テスト後にDBの変更を取り消す
    use DatabaseTransactions;

    // ① テスト用DBに接続している
    public function test_テスト用DBに接続している(): void
    {
        $this->assertSame(
            'timetable_test',
            DB::connection()->getDatabaseName()
        );
    }

    // ② 未ログインでは時間割を見られない
    public function test_未ログインでは時間割を見られない(): void
    {
        $response = $this->get('/entries');

        $response->assertRedirect('/login');
    }

    // ③ 一般学生は管理者画面に入れない
    public function test_学生は管理者画面に入れない(): void
    {
        // 準備：一般学生を作る
        $student = User::factory()->create([
            'is_admin' => false,
        ]);

        // 操作：学生で管理者画面へアクセス
        $response = $this
            ->actingAs($student)
            ->get('/admin/entries');

        // 確認：403で拒否される
        $response->assertForbidden();
    }

    // ④ 管理者は管理者画面に入れる
    public function test_管理者は管理者画面に入れる(): void
    {
        // 準備：管理者を作る
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        // 操作：管理者でアクセス
        $response = $this
            ->actingAs($admin)
            ->get('/admin/entries');

        // 確認：正常にページが表示される
        $response->assertOk();
    }

    // ⑤ 学生はお知らせを投稿できない
    public function test_学生はお知らせを投稿できない(): void
    {
        // 準備：一般学生を作る
        $student = User::factory()->create([
            'is_admin' => false,
        ]);

        // テスト用DBにある授業を取得
        $course = Course::firstOrFail();

        // 投稿前のお知らせ件数
        $before = DB::table('announcements')->count();

        // 操作：学生がお知らせを投稿
        $response = $this
            ->actingAs($student)
            ->post(
                "/courses/{$course->id}/announcements",
                [
                    'title' => 'テストのお知らせ',
                    'body' => '学生は投稿できない',
                ]
            );

        // 確認：403で拒否される
        $response->assertForbidden();

        // DBのお知らせ件数も変わらない
        $this->assertSame(
            $before,
            DB::table('announcements')->count()
        );
    }
}
