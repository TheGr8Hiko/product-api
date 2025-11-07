<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\SkuGenerator;
use PHPUnit\Framework\TestCase;

class SkuGeneratorTest extends TestCase
{
    public function testGenerateReturnsExpectedFormat(): void
    {
        $generator = new SkuGenerator();

        $sku = $generator->generate('Macbook Pro');

        $this->assertMatchesRegularExpression('/^PROD-MACB-[a-f0-9]{8}$/', $sku);
    }

    public function testGenerateIsRandom(): void
    {
        $generator = new SkuGenerator();

        $sku1 = $generator->generate('Macbook Pro');
        $sku2 = $generator->generate('Macbook Pro');

        $this->assertNotSame($sku1, $sku2);
    }

    public function testGeneratePadsShortNames(): void
    {
        $generator = new SkuGenerator();

        $sku = $generator->generate('TV');

        $this->assertMatchesRegularExpression('/^PROD-TVXX-[a-f0-9]{8}$/', $sku);
    }

    public function testGenerateStripsNonAlphanumericCharacters(): void
    {
        $generator = new SkuGenerator();

        $sku = $generator->generate('Iphone 16 Pro!');

        $this->assertMatchesRegularExpression('/^PROD-IPHO-[a-f0-9]{8}$/', $sku);
    }
}
