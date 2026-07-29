<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE sites MODIFY is_auto TINYINT(1) NULL DEFAULT NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE sites SET is_auto = 0 WHERE is_auto IS NULL');
        DB::statement('ALTER TABLE sites MODIFY is_auto TINYINT(1) NOT NULL DEFAULT 0');
    }
};
