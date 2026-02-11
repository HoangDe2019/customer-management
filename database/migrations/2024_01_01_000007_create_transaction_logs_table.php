<?php
// database/migrations/2024_01_01_000007_create_transaction_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained();

            $table->string('action'); // created, updated, status_changed
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->text('metadata')->nullable(); // JSON

            $table->timestamps();

            $table->index('transaction_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_logs');
    }
};
