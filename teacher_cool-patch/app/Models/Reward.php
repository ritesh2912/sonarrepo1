<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    use HasFactory;

    public const REWARD_CREDIT = 1;
    public const REWARD_DEBIT = 2;

    public const CONTENT_REWARD_TYPE = 1;
    public const REFFER_REWARD_TYPE = 2;
}
