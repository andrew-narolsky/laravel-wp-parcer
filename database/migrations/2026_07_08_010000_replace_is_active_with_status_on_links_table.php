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
            $table->string('status')->default('pending')->after('is_active');
            $table->text('failed_reason')->nullable()->after('status');
        });

        DB::table('links')->where('is_active', true)->update(['status' => 'published']);

        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->boolean('is_active')->default(false);
        });

        DB::table('links')->where('status', 'published')->update(['is_active' => true]);

        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn(['status', 'failed_reason']);
        });
    }
};
