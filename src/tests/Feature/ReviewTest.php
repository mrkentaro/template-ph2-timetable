<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use DatabaseTransactions;

    // ① 他人のレビューは削除できない
    public function test_他人のレビューは消せない(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();

        $course = Course::where('code', 'CS101')->firstOrFail();

        $review = $author->reviews()->create([
            'course_id' => $course->id,
            'body' => '板書が見やすい',
        ]);

        $response = $this->actingAs($other)
            ->delete("/reviews/{$review->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
        ]);
    }

    // ② 本人はレビューを削除できる
    public function test_本人はレビューを消せる(): void
    {
        $author = User::factory()->create();

        $course = Course::where('code', 'CS101')->firstOrFail();

        $review = $author->reviews()->create([
            'course_id' => $course->id,
            'body' => '授業が分かりやすかった',
        ]);

        $response = $this->actingAs($author)
            ->delete("/reviews/{$review->id}");

        $response->assertRedirect("/courses/{$course->id}");

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    // ③ 空のレビューは保存されない
    public function test_空のレビューは保存されない(): void
    {
        $student = User::factory()->create();

        $course = Course::where('code', 'CS101')->firstOrFail();

        $response = $this->actingAs($student)
            ->from("/courses/{$course->id}")
            ->post("/courses/{$course->id}/reviews", [
                'body' => '',
            ]);

        $response->assertRedirect("/courses/{$course->id}");
        $response->assertSessionHasErrors('body');

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);
    }

    // ④ 1000文字のレビューは保存できる
    public function test_1000文字のレビューは保存できる(): void
    {
        $student = User::factory()->create();

        $course = Course::where('code', 'CS101')->firstOrFail();

        $body = str_repeat('あ', 1000);

        $response = $this->actingAs($student)
            ->post("/courses/{$course->id}/reviews", [
                'body' => $body,
            ]);

        $response->assertRedirect("/courses/{$course->id}");

        $this->assertDatabaseHas('reviews', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'body' => $body,
        ]);
    }

    // ⑤ 1001文字のレビューは保存されない
    public function test_1001文字のレビューは保存されない(): void
    {
        $student = User::factory()->create();

        $course = Course::where('code', 'CS101')->firstOrFail();

        $response = $this->actingAs($student)
            ->from("/courses/{$course->id}")
            ->post("/courses/{$course->id}/reviews", [
                'body' => str_repeat('あ', 1001),
            ]);

        $response->assertRedirect("/courses/{$course->id}");
        $response->assertSessionHasErrors('body');

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);
    }
}
