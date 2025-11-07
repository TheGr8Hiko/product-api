<?php

declare(strict_types=1);

namespace App\Service;

class SkuGenerator
{
    public function generate(string $productName): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $productName) ?? '');

        $first4 = substr($normalized, 0, 4);
        $first4 = str_pad($first4, 4, 'X');

        $random = bin2hex(random_bytes(4));

        return sprintf('PROD-%s-%s', $first4, $random);
    }
}
