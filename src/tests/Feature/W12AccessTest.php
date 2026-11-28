<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class W12AccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_tests_use_the_isolated_postgresql_test_database(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('timetable_test', DB::connection()->getDatabaseName());
    }

    public function test_guest_cannot_open_timetable(): void
    {
        $this->get('/entries')->assertRedirect('/login');
    }

    public function test_student_cannot_open_admin_page(): void
    {
        $student = $this->user('student@example.com');

        $this->actingAs($student)
            ->get('/admin/entries')
            ->assertForbidden();
    }

    public function test_admin_can_open_admin_page(): void
    {
        $admin = $this->user('w12-admin@example.com', true);

        $this->actingAs($admin)
            ->get('/admin/entries')
            ->assertOk();
    }

    public function test_student_cannot_post_announcement(): void
    {
        Mail::fake();

        $student = $this->user('student@example.com');
        $course = $this->course();

        $this->actingAs($student)
            ->post('/courses/'.$course->id.'/announcements', [
                'title' => 'テストのお知らせ',
                'body' => '学生は投稿できない',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('announcements', [
            'course_id' => $course->id,
            'title' => 'テストのお知らせ',
        ]);
        Mail::assertNothingSent();
    }

    private function user(string $email, bool $isAdmin = false): User
    {
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => $email,
            'password' => 'password123',
        ]);
        $user->is_admin = $isAdmin;
        $user->save();

        return $user;
    }

    private function course(): Course
    {
        $id = DB::table('courses')->insertGetId([
            'code' => 'CS101',
            'name' => 'プログラミング入門',
            'teacher' => '担当教員',
            'day' => 1,
            'period' => 1,
            'room' => '教室',
            'credits' => 2,
            'required' => true,
            'capacity' => 40,
        ]);

        return Course::findOrFail($id);
    }
}
