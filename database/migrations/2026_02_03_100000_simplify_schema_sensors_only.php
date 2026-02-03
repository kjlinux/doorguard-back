<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old tables in correct order (drop tables with foreign keys first)
        Schema::dropIfExists('door_events');
        Schema::dropIfExists('sensor_events');
        Schema::dropIfExists('sensors');
        Schema::dropIfExists('doors');
        Schema::dropIfExists('card_holders');

        // Create simplified sensors table
        Schema::create('sensors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location');
            $table->string('mqtt_broker')->nullable();
            $table->integer('mqtt_port')->default(1883);
            $table->string('mqtt_topic')->unique();
            $table->string('status')->default('offline'); // 'online' or 'offline'
            $table->timestamp('last_seen')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('mqtt_topic');
        });

        // Create simplified sensor_events table
        Schema::create('sensor_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_id')->constrained('sensors')->cascadeOnDelete();
            $table->string('status')->default('open'); // 'open' or 'closed'
            $table->timestamp('detected_at');
            $table->timestamps();

            $table->index('detected_at');
            $table->index(['sensor_id', 'detected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_events');
        Schema::dropIfExists('sensors');
    }
};
