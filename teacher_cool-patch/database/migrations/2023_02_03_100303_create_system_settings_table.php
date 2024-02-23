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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('teacher_cool_weightage');
            $table->string('word_per_assignment');
            $table->integer('rate_per_assignment');
            $table->integer('hourly_rate_it_coding');
            $table->integer('actual_word_present')->nullable();
            $table->integer('word_conversion_rate')->nullable();
            $table->integer('discount')->default(0);
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
        Schema::dropIfExists('system_settings');
    }
};
