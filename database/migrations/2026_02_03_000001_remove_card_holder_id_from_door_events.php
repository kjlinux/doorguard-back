<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('door_events', function (Blueprint $table) {
            $table->dropForeign(['card_holder_id']);
            $table->dropColumn('card_holder_id');
        });
    }

    public function down(): void
    {
        Schema::table('door_events', function (Blueprint $table) {
            $table->foreignId('card_holder_id')->nullable()->constrained('card_holders')->nullOnDelete();
        });
    }
};
