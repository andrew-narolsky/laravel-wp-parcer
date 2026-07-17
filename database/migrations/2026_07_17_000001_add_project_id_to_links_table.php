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
            $table->foreignId('project_id')->nullable()->after('site_id')->constrained()->nullOnDelete();
        });

        $this->backfillProjects();
    }

    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
        });
    }

    private function backfillProjects(): void
    {
        $projectIds = [];

        DB::table('links')->select('id', 'url')->orderBy('id')->chunk(500, function ($links) use (&$projectIds) {
            foreach ($links as $link) {
                $domain = $this->domainFromUrl($link->url);

                if ($domain === null) {
                    continue;
                }

                if (!isset($projectIds[$domain])) {
                    $projectIds[$domain] = DB::table('projects')->insertGetId([
                        'name'       => $domain,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('links')->where('id', $link->id)->update(['project_id' => $projectIds[$domain]]);
            }
        });
    }

    private function domainFromUrl(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST) ?: parse_url("https://{$url}", PHP_URL_HOST);

        if (!$host || !str_contains($host, '.') || str_contains($host, ' ')) {
            return null;
        }

        return strtolower(preg_replace('/^www\./i', '', $host));
    }
};
