<?php
// database/migrations/2024_01_01_000008_create_momo_payments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('momo_payments', function (Blueprint $table) {
            $table->id();
            $table->string('momo_transaction_id')->unique();
            $table->foreignId('transaction_id')->nullable()->constrained();

            $table->string('customer_name');
            $table->decimal('amount', 15, 2);
            $table->string('transaction_type');

            $table->string('order_id')->unique();
            $table->string('request_id')->unique();
            $table->text('qr_code_url')->nullable();
            $table->text('pay_url')->nullable();
            $table->text('deeplink')->nullable();

            $table->integer('result_code')->nullable();
            $table->string('message')->nullable();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('momo_payments');
    }
};
