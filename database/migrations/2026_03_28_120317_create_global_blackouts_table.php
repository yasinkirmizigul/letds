<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('global_blackouts', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->timestamps();

            $table->index(['start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_blackouts');
    }
};
