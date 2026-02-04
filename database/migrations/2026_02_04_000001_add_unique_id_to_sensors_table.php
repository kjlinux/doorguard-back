<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->string('unique_id')->nullable()->after('id');
            $table->index('unique_id');
        });

        // Populate unique_id from mqtt_topic for existing sensors
        DB::table('sensors')->get()->each(function ($sensor) {
            // Extract unique_id from mqtt_topic (e.g., doorguard/sensor/sgci/event -> sgci)
            $parts = explode('/', $sensor->mqtt_topic);
            $uniqueId = $parts[2] ?? null;

            if ($uniqueId) {
                DB::table('sensors')
                    ->where('id', $sensor->id)
                    ->update(['unique_id' => $uniqueId]);
            }
        });

        // Make unique_id non-nullable and unique after populating existing data
        Schema::table('sensors', function (Blueprint $table) {
            $table->string('unique_id')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->dropIndex(['unique_id']);
            $table->dropColumn('unique_id');
        });
    }
};
