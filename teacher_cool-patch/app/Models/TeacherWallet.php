<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherWallet extends Model
{
    use HasFactory;

    public function billing_info(){
        return $this->hasOne(BillingInfo::class, 'teacher_id', 'user_id');
    }
}
