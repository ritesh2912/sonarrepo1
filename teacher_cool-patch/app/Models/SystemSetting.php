<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_cool_weightage',
        // 'teacher_weightage',
        'rate_per_assignment',
        'actual_word_present',
	    'word_conversion_rate'
    ];
}
