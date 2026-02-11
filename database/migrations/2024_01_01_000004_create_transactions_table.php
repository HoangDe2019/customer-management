<?php
// database/migrations/2024_01_01_000004_create_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->foreignId('agent_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained(); // Who created

            // Customer Info
            $table->string('customer_name');
            $table->string('cccd_number')->nullable(); // CCCD / CMND

            // Transaction Details
            $table->decimal('total_amount', 15, 2);
            $table->enum('transaction_type', ['Đáo', 'Rút']);

            // Fees (percentages stored as decimal)
            $table->decimal('pos_fee_percent', 5, 3)->default(1.067);
            $table->decimal('agent_fee_percent', 5, 3)->default(1.3);
            $table->decimal('pos_fee_amount', 15, 2);
            $table->decimal('agent_fee_amount', 15, 2);

            // Profit & Settlement
            $table->decimal('profit', 15, 2);
            $table->decimal('refund_to_agent', 15, 2);
            $table->decimal('agent_advance', 15, 2)->default(0);
            $table->decimal('net_settlement', 15, 2);

            // Status
            $table->enum('status', [
                'Chờ duyệt',
                'Đã duyệt',
                'Đang xử lý',
                'Chờ DR',
                'Hoàn thành',
                'Thất bại',
                'Đã hủy'
            ])->default('Chờ duyệt');

            $table->timestamp('transaction_date');
            $table->timestamps();
            $table->softDeletes();

            $table->index('transaction_id');
            $table->index('agent_id');
            $table->index('status');
            $table->index('transaction_date');
            $table->index(['agent_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
