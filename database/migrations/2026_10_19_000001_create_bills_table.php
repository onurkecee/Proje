<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('type', 30);
            $table->unsignedBigInteger('expected_amount_minor');
            $table->date('first_due_on');
            $table->boolean('repeat_monthly')->default(false);
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'id']);
            $table->index(['user_id', 'is_active', 'first_due_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
