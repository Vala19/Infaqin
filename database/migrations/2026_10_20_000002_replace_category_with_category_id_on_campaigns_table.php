<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullable in the database (SQLite cannot add a NOT NULL column to a filled table);
        // the admin form requires it.
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('campaigner_id')
                ->constrained()->restrictOnDelete();
        });

        $now = now();

        // Old values missing from the categories table get their own category so no data is lost.
        foreach (DB::table('campaigns')->distinct()->pluck('category') as $slug) {
            if (! DB::table('categories')->where('slug', $slug)->exists()) {
                DB::table('categories')->insert([
                    'name' => ucfirst($slug), 'slug' => $slug, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            DB::table('campaigns')->where('category', $slug)->update([
                'category_id' => DB::table('categories')->where('slug', $slug)->value('id'),
            ]);
        }

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('category', 40)->default('')->after('slug')->index();
        });

        foreach (DB::table('categories')->pluck('slug', 'id') as $id => $slug) {
            DB::table('campaigns')->where('category_id', $id)->update(['category' => $slug]);
        }

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
