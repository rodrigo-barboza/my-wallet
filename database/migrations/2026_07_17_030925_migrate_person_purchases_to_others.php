<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('purchases')
            ->whereIn('type', ['person', 'subscription'])
            ->update(['type' => 'others']);
    }

    public function down(): void
    {
        // Não é possível reverter deterministicamente
    }
};
