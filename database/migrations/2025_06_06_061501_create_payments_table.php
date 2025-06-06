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
            $table->string('customer_id');
            $table->string('customer_email');
            $table->string('reference_no');
            $table->date('payment_date');
            $table->string('currency');
            $table->decimal('amount', 15, 2);
            $table->decimal('usd_amount', 15, 2)->nullable();
            $table->boolean('processed')->default(false);
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['id', 'customer_email']);
            $table->index(['id', 'customer_email', 'customer_id']);
            $table->index('reference_no');
            $table->index('customer_email');
            $table->index('customer_id');
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
