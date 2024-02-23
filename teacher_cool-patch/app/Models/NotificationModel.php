<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationModel extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    public const NOTIFY_TO_ALL = 1;
    public const NOTIFY_TO_TEACHER = 2;
    public const NOTIFY_TO_STUDENT = 3;

    public const PUSH_NOTIFICATION = 1;
    public const EMAIL_NOTIFICATION = 2;

}
