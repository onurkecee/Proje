<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('credit_card_statement_id')->nullable();
            $table->unsignedBigInteger('loan_installment_id')->nullable();

            $table->foreign(['user_id', 'credit_card_statement_id'])
                ->references(['user_id', 'id'])
                ->on('credit_card_statements')
                ->cascadeOnDelete();
            $table->foreign(['user_id', 'loan_installment_id'])
                ->references(['user_id', 'id'])
                ->on('loan_installments')
                ->cascadeOnDelete();

            $table->index(['credit_card_statement_id', 'occurred_at']);
            $table->index(['loan_installment_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id', 'credit_card_statement_id']);
            $table->dropForeign(['user_id', 'loan_installment_id']);
            $table->dropIndex(['credit_card_statement_id', 'occurred_at']);
            $table->dropIndex(['loan_installment_id', 'occurred_at']);
            $table->dropColumn(['credit_card_statement_id', 'loan_installment_id']);
        });
    }
};
