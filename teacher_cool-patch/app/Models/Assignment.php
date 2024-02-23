<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'amount',
        'user_id',
        'teacher_id',
        'question',
        'question_description',
        'question_assingment_path',
        'subject_id',
        'category',
        'category_other',
        'title',
        'keyword',
        'word_count',
        'assignment_answer',
        'assignment_answer_path',
        'assignment_status',
        'is_paid_to_teacher',
        'due_date',
        'answered_on_date',
        'answered_on_time',
        'resubmit_request',
        'first_bid',
        'status_changed_on',
    ];

    public const ASSIGNMENT_STATUS_PENDING = 1;
    public const ASSIGNMENT_STATUS_SUBMITTED = 2;
    public const ASSIGNMENT_STATUS_APPROVED = 3;
    public const ASSIGNMENT_STATUS_REJECTED = 4;
    public const ASSIGNMENT_STATUS_RESUBMIT_REQUEST = 5;
    public const ASSIGNMENT_STATUS_RESUBMIT_ANSWER = 6;

    // public const WORD_COUNT_LIMIT = 350;


    public static function assignmentStatus()
    {
        return [
            ['value'=>0, 'name' => "All"],
            ['value'=>static::ASSIGNMENT_STATUS_PENDING, 'name' => "Pending"],
            ['value'=>static::ASSIGNMENT_STATUS_SUBMITTED, 'name' =>  "Submitted"],
            ['value'=>static::ASSIGNMENT_STATUS_APPROVED, 'name' =>  "Approved"],
            ['value'=>static::ASSIGNMENT_STATUS_REJECTED, 'name' =>  "Rejected"],
            ['value'=>static::ASSIGNMENT_STATUS_RESUBMIT_REQUEST, 'name' =>  "Resubmit"],
            ['value'=>static::ASSIGNMENT_STATUS_RESUBMIT_ANSWER, 'name' =>  "Resubmit Answered"],
        ];
    }

    public function order(){
        return $this->belongsTo(Order::class, 'assignment_id', 'id');
    }
}
