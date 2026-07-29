<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // The column was first added as NOT NULL DEFAULT false, so every existing site got
    // backfilled to "Manual" before it became nullable — reset that back to "Unknown".
    public function up(): void
    {
        DB::table('sites')->update(['is_auto' => null]);
    }

    public function down(): void
    {
        DB::table('sites')->whereNull('is_auto')->update(['is_auto' => false]);
    }
};
