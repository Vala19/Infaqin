<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('campaigns')->where('status', 'completed')->update(['status' => 'closed']);
    }

    /**
     * Not reversible: after the merge there is no way to tell which closed campaigns were "completed".
     */
    public function down(): void
    {
        //
    }
};
