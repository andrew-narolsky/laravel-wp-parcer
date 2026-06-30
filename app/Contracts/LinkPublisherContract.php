<?php

namespace App\Contracts;

use App\Models\Link;
use App\Models\Site;

interface LinkPublisherContract
{
    public function publish(Site $site, Link $link): array;
}
