<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stream_positions', function (Blueprint $table) {
            $table->string('stream_identifier')->primary();
            $table->integer('position')->default(0);
            $table->timestamp('lock_expiration_time')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stream_positions');
    }
};
