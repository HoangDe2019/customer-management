<?php
// database/migrations/2024_01_01_000006_create_eod_settlements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eod_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->onDelete('cascade');
            $table->date('settlement_date');

            $table->integer('total_transactions')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('total_pos_fee', 15, 2)->default(0);
            $table->decimal('total_agent_fee', 15, 2)->default(0);
            $table->decimal('total_profit', 15, 2)->default(0);
            $table->decimal('total_refund_to_agent', 15, 2)->default(0);
            $table->decimal('total_advance', 15, 2)->default(0);
            $table->decimal('net_settlement', 15, 2)->default(0);

            $table->foreignId('settled_by')->constrained('users');
            $table->timestamps();

            $table->unique(['agent_id', 'settlement_date']);
            $table->index('settlement_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eod_settlements');
    }
};
