<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->string('text_checksum', 64)->nullable()->after('text');
        });

        DB::statement('UPDATE links SET text_checksum = SHA2(text, 256)');

        Schema::table('links', function (Blueprint $table) {
            $table->unique(['site_id', 'url', 'anchor', 'type', 'text_checksum'], 'links_site_url_anchor_type_checksum_unique');
            $table->dropUnique('links_site_url_anchor_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->unique(['site_id', 'url', 'anchor', 'type'], 'links_site_url_anchor_type_unique');
            $table->dropUnique('links_site_url_anchor_type_checksum_unique');
        });

        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn('text_checksum');
        });
    }
};