<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'course_id',
        'body',
    ];

    // レビューを投稿したユーザー
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // レビューが紐づく授業
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
