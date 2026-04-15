<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Queue extends Model
{
    protected $table = 'queues';
    protected $fillable = [
        'student_id',
        'name',
        'priority_group', // 1 for Priority, 2 for Regular
        'processed_by',
        'status',
        'called_at',
    ];

    protected $casts = [
        'called_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    // Updated scope to respect priority first, then time
    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting')
            ->orderBy('priority_group', 'asc')
            ->orderBy('created_at', 'asc');
    }

    public function scopeCurrent($query)
    {
        return $query->where('status', 'serving')->first();
    }
}
