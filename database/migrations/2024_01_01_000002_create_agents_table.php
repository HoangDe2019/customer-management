<?php
// database/migrations/2024_01_01_000002_create_agents_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('agent_id')->unique(); // Original agent identifier
            $table->string('name');
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->json('allowed_users')->nullable(); // Array of allowed user emails
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('agent_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
