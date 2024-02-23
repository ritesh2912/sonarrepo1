<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    use HasFactory;

    public const CONTENT_CATEGORY_BOTH = 0;
    public const CONTENT_CATEGORY_IT = 1;
    public const CONTENT_CATEGORY_NON_IT = 2;

    public const CONTENT_PENDING = 1;
    public const CONTENT_APPROVE = 2;
    public const CONTENT_DISAPPROVE = 3;

    public const CONTENT_REJECT_DUPLICATE = 1;
    public const CONTENT_REJECT_HIGHLY_PRICED = 2;
    public const CONTENT_REJECT_OTHER = 3;
    
    protected $fillable = ['id','user_id','content_types_id','content_category','title','name','keyword','description','path','page_count','word_count','is_duplicate','uploaded_by_admin','is_published','is_approved','expected_amount','rejection_reason','is_pending','paid_to_seller','reject_status'];
    
    public static function getContentCategory()
    {
        return [
            ['value'=>static::CONTENT_CATEGORY_IT, 'name' => "IT"],
            ['value'=>static::CONTENT_CATEGORY_NON_IT, 'name' =>  "Non-IT"],
        ];
    }

    public static function getContentStatus()
    {
        return [
            ['value'=>static::CONTENT_APPROVE, 'name' => "Approve"],
            ['value'=>static::CONTENT_DISAPPROVE, 'name' =>  "disapprove"],
        ];
    }

    public function content_paid_orders()
    {   
        return $this->hasMany(Order::class, 'content_id')->where('payment_status', 2);
    }

    public function is_downloaded()
    {
        return $this->hasMany(UserDownload::class, 'content_id');
    }

    public function user_details()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user_other_details(){
        return $this->hasOne(UserDetails::class, 'user_id', 'user_id');
    }
}
