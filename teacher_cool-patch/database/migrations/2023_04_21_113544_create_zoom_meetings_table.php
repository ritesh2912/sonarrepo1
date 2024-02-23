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
        Schema::create('zoom_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable();
            $table->foreignId('teacher_id');
            $table->string('topic');
            // $table->integer('category');
            // $table->integer('subject_id');
            $table->string('join_link');
            $table->string('start_link');
            $table->string('pass_code');
            $table->longText('meta_data');
            $table->dateTime('schedule_time');
            $table->tinyInteger('status')->default(0);
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
        Schema::dropIfExists('zoom_meetings');
    }
};
