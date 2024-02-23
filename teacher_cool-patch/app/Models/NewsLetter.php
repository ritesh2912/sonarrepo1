<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsLetter extends Model
{
    use HasFactory;

    public const NEWSLETTER_TYPE_ALL = 1;
    public const NEWSLETTER_TYPE_SUBSCRIBED = 2;
}
