<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badge_door', function (Blueprint $table) {
            $table->id();
            $table->foreignId('badge_id')->constrained('badges')->cascadeOnDelete();
            $table->foreignId('door_id')->constrained('doors')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['badge_id', 'door_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badge_door');
    }
};
