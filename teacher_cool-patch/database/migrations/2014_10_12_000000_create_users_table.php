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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('profile_path')->nullable();
            $table->string('teacher_id_number')->nullable()->unique();            
            $table->tinyInteger('is_payment_block')->dafault(0)->comment('not-block=0; blocked=1;');
            $table->tinyInteger('user_type')->default(2)->comment('Teacher=1; Student=2');
            $table->string('stripe_custId')->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->tinyInteger('is_active')->default(0);
            $table->string('subscription_name')->nullable();
            $table->foreignId('subscription_plan_id');
            $table->date('subscription_expire_date');
            $table->tinyInteger('teacher_status')->default(1);
            $table->tinyInteger('requested_for_teacher')->default(0);
            $table->tinyInteger('is_newsletter_subscriber')->default(0);
            $table->integer('reffer_user_id')->nullable();
            $table->string('reffer_code')->nullable();
            $table->string('email_verify_code')->nullable();
            $table->string('social_type')->nullable();
            $table->string('facebook_id')->nullable();            
            // $table->tinyInteger('is_subscribe')->default(0)->comment('Not Subscribe=0; Subscribe=1');
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
};
