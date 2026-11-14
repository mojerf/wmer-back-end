<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 32);
            // Amount in tomans, copied from the order when the payment starts (never from the client).
            $table->unsignedInteger('amount');
            $table->string('status', 16)->default('pending');
            // Token issued by the gateway for this attempt; the callback is matched on it.
            $table->string('authority')->nullable();
            // Receipt number returned by the gateway after a successful verification.
            $table->string('reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'authority']);
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
