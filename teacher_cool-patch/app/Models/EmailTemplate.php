<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    public const WELCOME_EMAIL = 'welcome_email';
    public const NEWSLETTER_EMAIL = 'newsletter_email';

    protected $fillable = [
        'name',
        'slug',
        'body'
    ];
}
