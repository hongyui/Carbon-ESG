<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carbon_listing_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('worker_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('status', 20)->default('open')->index();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_jobs');
    }
};
