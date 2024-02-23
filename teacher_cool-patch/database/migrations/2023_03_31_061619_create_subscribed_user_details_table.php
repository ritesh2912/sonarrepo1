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
        Schema::create('subscribed_user_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->foreignId('subscription_plan_id');
            $table->string('order_id');
            $table->string('subscription_name')->nullable();
            $table->date('subscription_expire_date');
            $table->integer('file_download');
            $table->integer('assignment_request');
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
        Schema::dropIfExists('subscribed_user_details');
    }
};
