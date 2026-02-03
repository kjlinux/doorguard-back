<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_id')->constrained('sensors')->cascadeOnDelete();
            $table->timestamp('detected_at');
            $table->timestamps();

            $table->index('detected_at');
            $table->index(['sensor_id', 'detected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_events');
    }
};
