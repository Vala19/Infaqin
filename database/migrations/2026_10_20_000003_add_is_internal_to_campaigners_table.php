<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // true = Syaamil's own programmes, false = partner campaigners.
        Schema::table('campaigners', function (Blueprint $table) {
            $table->boolean('is_internal')->default(false)->after('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('campaigners', function (Blueprint $table) {
            $table->dropColumn('is_internal');
        });
    }
};
