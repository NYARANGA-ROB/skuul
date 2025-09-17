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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('fee_invoice_id')->constrained('fee_invoices')->onDelete('cascade');
            $table->string('mpesa_receipt_number')->nullable();
            $table->string('phone_number');
            $table->decimal('amount', 10, 2);
            $table->decimal('balance', 10, 2);
            $table->string('transaction_date');
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('merchant_request_id')->nullable();
            $table->string('checkout_request_id')->nullable();
            $table->text('result_description')->nullable();
            $table->text('result_code')->nullable();
            $table->string('payment_mode')->default('mpesa');
            $table->boolean('receipt_sent')->default(false);
            $table->string('payment_plan_type')->nullable();
            $table->unsignedBigInteger('payment_plan_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
