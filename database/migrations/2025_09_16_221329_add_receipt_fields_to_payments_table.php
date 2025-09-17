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
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'receipt_path')) {
                $table->string('receipt_path')->nullable()->after('payment_plan_id');
            }
            if (!Schema::hasColumn('payments', 'receipt_sent_at')) {
                $table->timestamp('receipt_sent_at')->nullable()->after('receipt_path');
            }
            if (!Schema::hasColumn('payments', 'receipt_sent')) {
                $table->boolean('receipt_sent')->default(false)->after('receipt_sent_at');
            }
            if (!Schema::hasColumn('payments', 'notes')) {
                $table->text('notes')->nullable()->after('payment_mode');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'receipt_path',
                'receipt_sent_at',
                'receipt_sent',
                'payment_mode',
                'notes'
            ]);
        });
    }
};
