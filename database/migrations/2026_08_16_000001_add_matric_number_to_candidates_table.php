<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('matric_number', 50)->nullable()->after('name');
            $table->unique(['position_id', 'matric_number']);
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropUnique(['position_id', 'matric_number']);
            $table->dropColumn('matric_number');
        });
    }
};
