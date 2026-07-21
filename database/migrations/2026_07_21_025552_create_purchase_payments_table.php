<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->integer('month');
            $table->integer('year');
            $table->timestamp('paid_at');
            $table->timestamps();

            $table->unique(['purchase_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
    }
};
