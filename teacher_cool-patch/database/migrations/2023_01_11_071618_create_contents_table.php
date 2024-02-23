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
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->default(0);
            $table->foreignId('content_types_id');
            $table->integer('content_category');
            // $table->string('title')->nullable();
            $table->string('name');
            $table->longText('keyword');
            $table->longText('description')->nullable();
            $table->string('path');
            $table->string('page_count');
            $table->string('word_count')->nullable();
            $table->enum('is_duplicate',[0,1])->default(0);
            $table->longText('duplicate_id')->nullable();
            $table->tinyInteger('uploaded_by_admin')->default(2);
            $table->tinyInteger('is_published')->default(0);
            $table->tinyInteger('is_approved')->default(1);
            $table->tinyInteger('is_exchange')->default(0);
            $table->float('expected_amount', 8, 2)->nullable();
            $table->tinyInteger('reject_status')->nullable();            
            $table->text('rejection_reason')->nullable();
            $table->tinyInteger('paid_to_seller')->default(0);
            $table->enum('is_pending',[0,1])->default(0);
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
        Schema::dropIfExists('contents');
    }
};
