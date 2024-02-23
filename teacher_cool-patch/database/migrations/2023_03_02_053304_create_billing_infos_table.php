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
        Schema::create('billing_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->unique();
            $table->string('bank_name');
            $table->string('account_holder_name');
            $table->string('account_number');
            $table->string('ifsc_code')->nullable();
            $table->string('routing_number')->nullable();            
            $table->string('firm_name')->nullable();
            $table->string('gst_number')->nullable();
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
        Schema::dropIfExists('billing_infos');
    }
};
