<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('doors')) {
            Schema::create('doors', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('location')->nullable();
                $table->foreignId('sensor_id')->nullable()->constrained('sensors')->nullOnDelete();
                $table->timestamps();
            });
        } else {
            Schema::table('doors', function (Blueprint $table) {
                if (!Schema::hasColumn('doors', 'sensor_id')) {
                    $table->foreignId('sensor_id')->nullable()->constrained('sensors')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('doors');
    }
};
