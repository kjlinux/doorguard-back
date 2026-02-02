<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('door_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('door_id')->constrained('doors')->cascadeOnDelete();
            $table->string('status'); // 'open' or 'closed'
            $table->foreignId('card_holder_id')->nullable()->constrained('card_holders')->nullOnDelete();
            $table->timestamp('timestamp');
            $table->timestamps();

            $table->index('timestamp');
            $table->index('status');
            $table->index(['door_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('door_events');
    }
};
