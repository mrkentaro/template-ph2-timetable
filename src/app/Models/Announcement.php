<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'body',
    ];

    // お知らせが属する授業
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
