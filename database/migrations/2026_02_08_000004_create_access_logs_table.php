<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('badge_id')->nullable()->constrained('badges')->nullOnDelete();
            $table->foreignId('door_id')->nullable()->constrained('doors')->nullOnDelete();
            $table->foreignId('sensor_id')->nullable()->constrained('sensors')->nullOnDelete();
            $table->string('status'); // accepted, refused, rejected, forced_open
            $table->string('badge_uid'); // raw UID scanned
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('responded_at');
            $table->index(['door_id', 'responded_at']);
            $table->index(['badge_id', 'responded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};
