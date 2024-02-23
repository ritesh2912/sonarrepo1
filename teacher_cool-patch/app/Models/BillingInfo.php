<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'account_holder_name',
        'bank_name',
        'account_number',
        'ifsc_code',
        'firm_name',
        'gst_number',
        'routing_number',
    ];
}
