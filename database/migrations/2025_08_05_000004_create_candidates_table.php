<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('photo_path')->nullable();
            $table->text('manifesto')->nullable();
            $table->timestamps();

            $table->index('position_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
