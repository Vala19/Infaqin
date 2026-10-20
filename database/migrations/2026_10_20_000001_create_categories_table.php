<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The categories that used to live in Campaign::CATEGORIES (slug => name).
     * Copied here so this migration does not depend on the model.
     */
    private const CATEGORIES = [
        'education' => 'Education',
        'mosque' => 'Mosque',
        'quran' => "Qur'an",
        'health' => 'Health',
        'disaster' => 'Disaster Relief',
        'orphans' => 'Orphans',
    ];

    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        $now = now();

        DB::table('categories')->insert(collect(self::CATEGORIES)
            ->map(fn ($name, $slug) => ['name' => $name, 'slug' => $slug, 'created_at' => $now, 'updated_at' => $now])
            ->values()
            ->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
