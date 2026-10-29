<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('bill_occurrence_id')->nullable();
            $table->unique('bill_occurrence_id');
            $table->foreign(['user_id', 'bill_occurrence_id'])
                ->references(['user_id', 'id'])
                ->on('bill_occurrences')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id', 'bill_occurrence_id']);
            $table->dropUnique(['bill_occurrence_id']);
            $table->dropColumn('bill_occurrence_id');
        });
    }
};
