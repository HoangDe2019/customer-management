<?php
// database/migrations/2024_01_01_000005_create_daily_advances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_advances', function (Blueprint $table) {
            $table->id();
            $table->date('advance_date');
            $table->foreignId('agent_id')->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->decimal('advance_amount', 15, 2);
            $table->boolean('is_settled')->default(false);
            $table->timestamp('settled_at')->nullable();
            $table->string('settlement_transaction_id')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'advance_date', 'is_settled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_advances');
    }
};
