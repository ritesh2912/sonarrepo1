<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory; 

    public const SUBSCRIPTION_ORDER_TYPE = 1;
    public const SINGLE_ASSINGMENT_ORDER_TYPE = 2;
    public const CONTENT_PURCHASE_ORDER_TYPE = 3;
    public const OTHER_ORDER_TYPE = 4;

    public const ORDER_PAYMENT_PENDING = 1;
    public const ORDER_PAYMENT_PAID = 2;
    public const ORDER_PAYMENT_FAILED = 3;
}
