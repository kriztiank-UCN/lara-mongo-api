<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('mysql')->create('customer_sql', function (Blueprint  $table) {
         $table->id();
         $table->uuid('guid')->unique();
         $table->string('first_name');
         $table->string('family_name');
         $table->string('email');
         $table->text('address');
         $table->timestamps();
         });
   }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_s_q_l_s');
    }
};
