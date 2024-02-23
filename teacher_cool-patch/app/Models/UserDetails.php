<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class UserDetails extends Model
{
    use HasFactory;
    protected $fillable =[
        'user_id',
        'gender',
        'age',
        'phone_code',
        'contact',
        'city',
        'state',
        'country',
        'qualification',
        'university',
        'currency'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
