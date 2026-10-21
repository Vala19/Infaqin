<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The payment method is chosen in the Snap popup, so it is unknown until Midtrans notifies us.
 */
return new class extends Migration
{
    /**
     * SQLite rebuilds the table for ->change() and turns foreign keys off while doing so.
     * PRAGMA foreign_keys is ignored inside a transaction, so without this the
     * "drop table donations" step would cascade and delete every comment.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->string('payment_method', 30)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('donations')->whereNull('payment_method')->update(['payment_method' => '']);

        Schema::table('donations', function (Blueprint $table) {
            $table->string('payment_method', 30)->nullable(false)->change();
        });
    }
};
