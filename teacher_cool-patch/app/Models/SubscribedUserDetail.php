<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscribedUserDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_name',
        'order_id',
        'subscription_plan_id',
        'subscription_expire_date',
        'file_download',
        'assignment_request',
    ];
}
