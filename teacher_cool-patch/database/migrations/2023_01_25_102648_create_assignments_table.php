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
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->string('assignment_id');
            $table->string('amount')->nullable();
            $table->foreignId('user_id');
            $table->foreignId('teacher_id')->dafault(0);
            $table->longText('question');
            $table->longText('question_description');
            $table->string('question_assingment_path')->nullable();
            $table->foreignId('subject_id')->nullable();
            $table->tinyInteger('category');
            $table->string('category_other')->nullable();
            $table->string('title')->nullable();
            $table->string('keyword')->nullable();
            $table->string('word_count')->nullable();
            $table->longText('assignment_answer')->nullable();
            $table->string('assignment_answer_path')->nullable();
            $table->tinyInteger('is_paid_for_assignment')->dafault(0)->comment('not-paid=0; paid=1;');
            $table->tinyInteger('assignment_status')->dafault(1)->comment('pending=1; submitted=2; approved=3');
            $table->tinyInteger('is_paid_to_teacher')->dafault(0)->comment('not-paid=0; paid=1;');
            $table->dateTime('due_date')->nullable();
            $table->date('answered_on_date')->nullable();
            $table->time('answered_on_time')->nullable();
            $table->integer('assignment_hours')->nullable();
            $table->dateTime('status_changed_on')->nullable();
            $table->integer('resubmit_request')->default(0);
            $table->dateTime('first_bid');
            // $table->dateTime('due_date')->nullable();
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
        Schema::dropIfExists('assignments');
    }
};
