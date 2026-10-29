<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_card_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('due_on');
            $table->unsignedBigInteger('amount_minor');
            $table->unsignedBigInteger('paid_amount_minor')->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign(['user_id', 'account_id'])
                ->references(['user_id', 'id'])
                ->on('accounts')
                ->cascadeOnDelete();
            $table->unique(['user_id', 'id']);
            $table->unique(['account_id', 'period_start']);
            $table->index(['user_id', 'paid_at', 'due_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_card_statements');
    }
};
