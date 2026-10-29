<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('lender', 120)->nullable();
            $table->unsignedBigInteger('total_repayment_minor');
            $table->unsignedBigInteger('installment_amount_minor');
            $table->unsignedSmallInteger('installment_count');
            $table->date('first_due_on');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'id']);
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
