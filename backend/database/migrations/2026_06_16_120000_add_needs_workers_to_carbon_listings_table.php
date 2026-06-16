<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carbon_listings', function (Blueprint $table) {
            $table->boolean('needs_workers')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('carbon_listings', function (Blueprint $table) {
            $table->dropColumn('needs_workers');
        });
    }
};
