<?php

namespace App\Services;

readonly class XmlRpcBase64Value
{
    public function __construct(public string $raw) {}
}