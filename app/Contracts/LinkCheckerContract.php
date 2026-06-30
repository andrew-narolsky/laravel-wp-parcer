<?php

namespace App\Contracts;

use App\DTO\LinkCheckResult;
use App\Models\Link;
use App\Models\Site;

interface LinkCheckerContract
{
    public function check(Site $site, Link $link): LinkCheckResult;
}