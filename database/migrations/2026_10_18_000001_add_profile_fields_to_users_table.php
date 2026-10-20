<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable()->unique()->after('name');
            $table->date('date_of_birth')->nullable()->after('email_verified_at');
            $table->string('phone', 20)->nullable()->after('date_of_birth');
            $table->boolean('is_admin')->default(false)->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'date_of_birth', 'phone', 'is_admin']);
        });
    }
};
