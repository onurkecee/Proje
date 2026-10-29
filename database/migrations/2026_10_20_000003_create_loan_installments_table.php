<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loan_id');
            $table->unsignedSmallInteger('installment_number');
            $table->date('due_on');
            $table->unsignedBigInteger('amount_minor');
            $table->unsignedBigInteger('paid_amount_minor')->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign(['user_id', 'loan_id'])
                ->references(['user_id', 'id'])
                ->on('loans')
                ->cascadeOnDelete();
            $table->unique(['user_id', 'id']);
            $table->unique(['loan_id', 'installment_number']);
            $table->index(['user_id', 'paid_at', 'due_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_installments');
    }
};
