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

class CreditSummaryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_自分の承認済み授業の単位を正しく合計できる(): void
    {
        $student = $this->user();
        $this->entry($student, $this->course(2), 'approved');
        $this->entry($student, $this->course(1), 'approved');

        $this->actingAs($student)
            ->get('/entries')
            ->assertOk()
            ->assertViewHas('totalCredits', 3)
            ->assertSeeText('3 / 24単位')
            ->assertSeeText('上限以内');
    }

    public function test_申請中の授業は単位に含まれない(): void
    {
        $student = $this->user();
        $this->entry($student, $this->course(4), 'pending');

        $this->actingAs($student)
            ->get('/entries')
            ->assertOk()
            ->assertViewHas('totalCredits', 0)
            ->assertSeeText('0 / 24単位');
    }

    public function test_却下済みの授業は単位に含まれない(): void
    {
        $student = $this->user();
        $this->entry($student, $this->course(4), 'rejected');

        $this->actingAs($student)
            ->get('/entries')
            ->assertOk()
            ->assertViewHas('totalCredits', 0)
            ->assertSeeText('0 / 24単位');
    }

    public function test_他人の履修データは集計に含まれない(): void
    {
        $student = $this->user();
        $otherStudent = $this->user();
        $this->entry($student, $this->course(2), 'approved');
        $this->entry($otherStudent, $this->course(20), 'approved');

        $this->actingAs($student)
            ->get('/entries?user_id='.$otherStudent->id)
            ->assertOk()
            ->assertViewHas('totalCredits', 2)
            ->assertSeeText('2 / 24単位');
    }

    public function test_24単位では上限を超えていないと表示される(): void
    {
        $student = $this->user();
        $this->entry($student, $this->course(24), 'approved');

        $this->actingAs($student)
            ->get('/entries')
            ->assertOk()
            ->assertViewHas('isCreditLimitExceeded', false)
            ->assertSeeText('24 / 24単位')
            ->assertSeeText('上限以内')
            ->assertDontSeeText('上限超過');
    }

    public function test_25単位では上限超過と表示される(): void
    {
        $student = $this->user();
        $this->entry($student, $this->course(25), 'approved');

        $this->actingAs($student)
            ->get('/entries')
            ->assertOk()
            ->assertViewHas('isCreditLimitExceeded', true)
            ->assertSeeText('25 / 24単位')
            ->assertSeeText('上限超過');
    }

    public function test_学生がリクエストを書き換えても単位上限を変更できない(): void
    {
        $student = $this->user();
        $this->entry($student, $this->course(25), 'approved');

        $this->actingAs($student)
            ->get('/entries?credit_limit=100')
            ->assertOk()
            ->assertViewHas('creditLimit', 24)
            ->assertSeeText('25 / 24単位')
            ->assertSeeText('上限超過');
    }

    public function test_未ログインでは単位確認画面を利用できない(): void
    {
        $this->get('/entries')->assertRedirect('/login');
    }

    public function test_単位確認画面が正常に表示される(): void
    {
        $student = $this->user();

        $this->actingAs($student)
            ->get('/entries')
            ->assertOk()
            ->assertSeeText('今学期の単位')
            ->assertSeeText('0 / 24単位');
    }

    public function test_未履修の必修授業が正しく表示される(): void
    {
        $student = $this->user();
        $approvedRequired = $this->course(2, true, '承認済み必修');
        $missingRequired = $this->course(2, true, '未履修必修');
        $optional = $this->course(2, false, '選択授業');
        $this->entry($student, $approvedRequired, 'approved');

        $this->actingAs($student)
            ->get('/entries')
            ->assertOk()
            ->assertViewHas('missingRequiredCourses', function ($courses) use (
                $approvedRequired,
                $missingRequired,
                $optional
            ) {
                return $courses->contains('id', $missingRequired->id)
                    && ! $courses->contains('id', $approvedRequired->id)
                    && ! $courses->contains('id', $optional->id);
            })
            ->assertSeeText('未履修必修');
    }

    public function test_申請中と却下済みの必修授業は未履修として表示される(): void
    {
        $student = $this->user();
        $pending = $this->course(2, true, '申請中必修');
        $rejected = $this->course(2, true, '却下済み必修');
        $this->entry($student, $pending, 'pending');
        $this->entry($student, $rejected, 'rejected');

        $this->actingAs($student)
            ->get('/entries')
            ->assertOk()
            ->assertViewHas('missingRequiredCourses', function ($courses) use (
                $pending,
                $rejected
            ) {
                return $courses->contains('id', $pending->id)
                    && $courses->contains('id', $rejected->id);
            })
            ->assertSeeText('申請中必修')
            ->assertSeeText('申請中')
            ->assertSeeText('却下済み必修')
            ->assertSeeText('却下済み');
    }

    public function test_他人の承認済み必修は自分の未履修として表示される(): void
    {
        $student = $this->user();
        $otherStudent = $this->user();
        $required = $this->course(2, true, '他人だけが履修した必修');
        $this->entry($otherStudent, $required, 'approved');

        $this->actingAs($student)
            ->get('/entries')
            ->assertOk()
            ->assertViewHas(
                'missingRequiredCourses',
                fn ($courses) => $courses->contains('id', $required->id)
            )
            ->assertSeeText('他人だけが履修した必修');
    }

    public function test_必修をすべて承認済みにすると未履修なしと表示される(): void
    {
        $student = $this->user();

        Course::query()
            ->where('required', true)
            ->get()
            ->each(function (Course $course) use ($student) {
                $this->entry($student, $course, 'approved');
            });

        $this->actingAs($student)
            ->get('/entries')
            ->assertOk()
            ->assertViewHas(
                'missingRequiredCourses',
                fn ($courses) => $courses->isEmpty()
            )
            ->assertSeeText('未履修の必修授業はありません。');
    }

    private function user(): User
    {
        return User::create([
            'name' => '単位確認テスト学生',
            'email' => Str::uuid().'@example.com',
            'password' => Hash::make('password123'),
        ]);
    }

    private function course(
        int $credits,
        bool $required = false,
        ?string $name = null
    ): Course {
        $code = 'W13-'.Str::upper(Str::random(12));
        $id = DB::table('courses')->insertGetId([
            'code' => $code,
            'name' => $name ?? $code.' テスト授業',
            'teacher' => 'テスト教員',
            'day' => 1,
            'period' => 1,
            'room' => 'テスト教室',
            'credits' => $credits,
            'required' => $required,
            'capacity' => 40,
        ]);

        return Course::findOrFail($id);
    }

    private function entry(
        User $user,
        Course $course,
        string $status
    ): TimetableEntry {
        return TimetableEntry::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => $status,
            'decided_at' => $status === 'pending' ? null : now(),
        ]);
    }
}
