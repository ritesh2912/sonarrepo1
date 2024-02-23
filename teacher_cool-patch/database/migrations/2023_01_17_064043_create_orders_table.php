<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->string('checkout_session_id')->unique();
            $table->foreignId('user_id');
            $table->foreignId('subscription_plan_id')->nullable();
            $table->foreignId('assignment_id')->nullable();
            $table->foreignId('content_id')->nullable();
            $table->tinyInteger('order_type');
            $table->tinyInteger('payment_status')->default(2)->comment('paid=2: not-paid=1: canceled=3');
            $table->float('total_amount', 8, 3);
            $table->float('net_amount', 8, 3);
            $table->float('total_amount_inr',8,3);
            $table->string('currency');
            $table->float('discount')->default(0);
            $table->float('tax')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
