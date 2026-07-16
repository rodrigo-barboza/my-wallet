<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('card_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('type');
            $table->unsignedTinyInteger('payment_day')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->decimal('amount', 10, 2);
            $table->unsignedSmallInteger('installments_total')->nullable();
            $table->date('start_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('card_id');
            $table->index(['user_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
