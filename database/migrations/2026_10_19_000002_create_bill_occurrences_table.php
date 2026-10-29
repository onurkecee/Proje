<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bill_id');
            $table->date('due_date');
            $table->unsignedBigInteger('amount_minor');
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('paid_amount_minor')->nullable();
            $table->timestamps();

            $table->foreign(['user_id', 'bill_id'])
                ->references(['user_id', 'id'])
                ->on('bills')
                ->cascadeOnDelete();
            $table->unique(['user_id', 'id']);
            $table->unique(['bill_id', 'due_date']);
            $table->index(['user_id', 'paid_at', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_occurrences');
    }
};
