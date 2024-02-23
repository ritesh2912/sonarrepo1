<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ZoomMeeting extends Model
{
    use HasFactory;

    public const ZOOM_REQUEST_PENDING = 0;
    public const ZOOM_REQUEST_APPROVED = 1;
    public const ZOOM_REQUEST_REJECTED = 2;

    protected $fillable = [ 'topic', 'student_id', 'teacher_id', 'join_link', 'start_link', 'pass_code', 'meta_data', 'schedule_time', 'status'];

    public function student()
    {   
        return $this->belongsTo(User::class, 'student_id');
    }

    public function teacher()
    {   
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
