<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location');
            $table->foreignId('door_id')->constrained('doors')->cascadeOnDelete();
            $table->string('mqtt_broker')->nullable();
            $table->integer('mqtt_port')->default(1883);
            $table->string('mqtt_topic');
            $table->string('status')->default('offline'); // 'online' or 'offline'
            $table->timestamp('last_seen')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('mqtt_topic');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensors');
    }
};
