<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->unique(['site_id', 'url', 'anchor', 'type'], 'links_site_url_anchor_type_unique');
        });

        Schema::table('links', function (Blueprint $table) {
            $table->dropUnique('links_site_url_anchor_unique');
        });
    }

    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->unique(['site_id', 'url', 'anchor'], 'links_site_url_anchor_unique');
        });

        Schema::table('links', function (Blueprint $table) {
            $table->dropUnique('links_site_url_anchor_type_unique');
        });
    }
};