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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->bigInteger('account_number')->unique(); // Número de cuenta único
            $table->string('name');
            $table->string('lastname');
            $table->string('address');
            $table->string('phone_number')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('national_id')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Suspended'])->default('Active');
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
