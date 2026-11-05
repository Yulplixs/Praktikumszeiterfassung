<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupervisionEntry extends Model
{
    protected $table = 'supervision_entries';

    protected $fillable = [
        'school_year_id',
        'half_year',
        'class_id',
        'teacher_id',
        'student_count',
    ];

    protected $casts = [
        'school_year_id' => 'integer',
        'half_year' => 'integer',
        'class_id' => 'integer',
        'teacher_id' => 'integer',
        'student_count' => 'integer',
        'factor_snapshot' => 'decimal:4',
        'weeks_snapshot' => 'decimal:4',
        'hours_per_week' => 'decimal:4',
        'hours_half_year' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}