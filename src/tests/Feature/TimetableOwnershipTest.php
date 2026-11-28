<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class TimetableOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    public function test_two_users_can_register_the_same_course(): void
    {
        [$firstUser, $secondUser] = $this->users();
        $course = $this->course('所有者テスト共通授業', 1);

        $this->actingAs($firstUser)
            ->post('/entries', ['course_id' => $course->id])
            ->assertRedirect('/entries');

        $this->actingAs($secondUser)
            ->post('/entries', ['course_id' => $course->id])
            ->assertRedirect('/entries');

        $this->assertDatabaseHas('timetable_entries', [
            'user_id' => $firstUser->id,
            'course_id' => $course->id,
        ]);
        $this->assertDatabaseHas('timetable_entries', [
            'user_id' => $secondUser->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_entries_and_registered_status_are_limited_to_the_current_user(): void
    {
        [$firstUser, $secondUser] = $this->users();
        $firstCourse = $this->course('所有者テスト授業1', 1);
        $secondCourse = $this->course('所有者テスト授業2', 2);

        TimetableEntry::create([
            'user_id' => $firstUser->id,
            'course_id' => $firstCourse->id,
        ]);
        TimetableEntry::create([
            'user_id' => $secondUser->id,
            'course_id' => $secondCourse->id,
        ]);

        $this->actingAs($firstUser)
            ->get('/entries')
            ->assertOk()
            ->assertSee('所有者テスト授業1')
            ->assertDontSee('所有者テスト授業2');

        $this->actingAs($firstUser)
            ->get('/courses')
            ->assertOk()
            ->assertSeeInOrder([
                $firstCourse->code,
                '登録済み',
                $secondCourse->code,
                '登録',
            ]);
    }

    public function test_user_cannot_delete_another_users_entry(): void
    {
        [$firstUser, $secondUser] = $this->users();
        $course = $this->course('削除認可テスト授業', 1);
        $entry = TimetableEntry::create([
            'user_id' => $firstUser->id,
            'course_id' => $course->id,
        ]);

        $this->actingAs($secondUser)
            ->delete('/entries/'.$entry->id)
            ->assertForbidden();

        $this->assertDatabaseHas('timetable_entries', ['id' => $entry->id]);
    }

    private function users(): array
    {
        return [
            User::create([
                'name' => 'ユーザー1',
                'email' => 'first@example.com',
                'password' => Hash::make('password123'),
            ]),
            User::create([
                'name' => 'ユーザー2',
                'email' => 'second@example.com',
                'password' => Hash::make('password123'),
            ]),
        ];
    }

    private function course(string $name, int $period): Course
    {
        $code = 'OWN-'.Str::upper(Str::random(12));
        $id = DB::table('courses')->insertGetId([
            'code' => $code,
            'name' => $name,
            'teacher' => '担当教員',
            'day' => 1,
            'period' => $period,
            'room' => '教室',
            'credits' => 2,
            'required' => false,
            'capacity' => 40,
        ]);

        return Course::findOrFail($id);
    }
}
