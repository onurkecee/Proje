<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id');
            $table->foreignId('destination_account_id')->nullable();
            $table->foreignId('category_id')->nullable();
            $table->string('type', 20);
            $table->unsignedBigInteger('amount_minor');
            $table->text('description')->nullable();
            $table->date('occurred_at');
            $table->timestamps();

            $table->foreign(['user_id', 'account_id'])
                ->references(['user_id', 'id'])
                ->on('accounts')
                ->cascadeOnDelete();
            $table->foreign(['user_id', 'destination_account_id'])
                ->references(['user_id', 'id'])
                ->on('accounts')
                ->cascadeOnDelete();
            $table->foreign(['user_id', 'category_id'])
                ->references(['user_id', 'id'])
                ->on('categories')
                ->restrictOnDelete();
            $table->index(['user_id', 'occurred_at']);
            $table->index(['account_id', 'occurred_at']);
            $table->index(['category_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
