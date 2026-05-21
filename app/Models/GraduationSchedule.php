<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GraduationSchedule extends Model
{
    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'notice_text',
        'is_important',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_important' => 'boolean',
    ];
}
