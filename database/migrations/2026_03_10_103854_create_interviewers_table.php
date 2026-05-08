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
        Schema::create('interviewers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('bio')->nullable();
            $table->json('domains')->nullable(); // Backend, Frontend, DevOps, AI/ML, Mobile
            $table->json('interview_types')->nullable(); // DSA, System Design, Behavioral
            $table->string('experience_level')->nullable(); // Senior, Staff, Principal
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->decimal('rating', 3, 2)->nullable();
            $table->json('badges')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interviewers');
    }
};
