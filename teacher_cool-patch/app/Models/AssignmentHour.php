<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignmentHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'assignment_id',
        'estimated_hours',
    ];
}
