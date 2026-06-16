<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_job_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_job_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('worker_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->dateTime('datetime_start');
            $table->dateTime('datetime_end');
            $table->string('before_image_path');
            $table->string('after_image_path');
            $table->text('content');
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('reviewer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('review_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_job_reports');
    }
};
