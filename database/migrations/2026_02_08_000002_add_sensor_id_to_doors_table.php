<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doors', function (Blueprint $table) {
            if (!Schema::hasColumn('doors', 'sensor_id')) {
                $table->foreignId('sensor_id')->nullable()->constrained('sensors')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('doors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sensor_id');
        });
    }
};
