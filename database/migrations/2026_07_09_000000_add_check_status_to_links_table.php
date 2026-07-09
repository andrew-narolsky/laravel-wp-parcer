<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->string('check_status')->default('unknown')->after('failed_reason');
            $table->text('check_error')->nullable()->after('check_status');
            $table->timestamp('checked_at')->nullable()->after('check_error');
        });
    }

    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn(['check_status', 'check_error', 'checked_at']);
        });
    }
};