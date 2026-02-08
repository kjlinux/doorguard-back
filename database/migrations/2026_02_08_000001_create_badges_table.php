<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('holder_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('uid');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
