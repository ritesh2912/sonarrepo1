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
        Schema::create('job_internships', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('positions_count');
            $table->string('recruiter_email');
            $table->string('job_category')->nullable();
            $table->string('type');
            $table->string('status')->comment('Open=1; close=2;');;;
            $table->string('skills');
            $table->string('department');
            $table->string('experience');
            $table->string('currency');
            $table->string('salary');
            $table->string('document_path');
            $table->longText('description');
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
        Schema::dropIfExists('job_internships');
    }
};
