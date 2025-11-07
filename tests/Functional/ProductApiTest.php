<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductApiTest extends WebTestCase
{
     protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        return $em;
    }

    private function clearProducts(): void
    {
        $em = $this->getEntityManager();
        $em->createQuery('DELETE FROM App\Entity\Product p')->execute();
    }

    public function testCreateProductSuccess(): void
    {
        $client = static::createClient();
        $this->clearProducts();

        $payload = [
            'name' => 'Test Product',
            'price' => 149.99,
        ];

        $client->request(
            'POST',
            '/api/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(201);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $data);
        $this->assertSame('Test Product', $data['name']);
        $this->assertSame(149.99, $data['price']);
        $this->assertArrayHasKey('sku', $data);

        $this->assertMatchesRegularExpression('/^PROD-TEST-[a-f0-9]{8}$/', $data['sku']);
    }

        public function testCreateProductInvalidPayloadReturnsBadRequest(): void
    {
        $client = static::createClient();
        $this->clearProducts();

        $payload = [
            'price' => -10,
        ];

        $client->request(
            'POST',
            '/api/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('errors', $data);
        $this->assertGreaterThanOrEqual(1, count($data['errors']));
    }

        
        public function testGetProductSuccess(): void
    {
        $client = static::createClient();
        $this->clearProducts();

        $em = $this->getEntityManager();

        $product = new Product();
        $product
            ->setName('Existing Product')
            ->setPrice(50.0)
            ->setSku('PROD-EXIS-12345678');

        $em->persist($product);
        $em->flush();

        $client->request('GET', '/api/products/' . $product->getIdAsString());

        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Existing Product', $data['name']);
        $this->assertEquals(50.0, $data['price']);
        $this->assertEquals('PROD-EXIS-12345678', $data['sku']);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('createdAt', $data);
    }


     public function testGetProductNotFound(): void
    {
        $client = static::createClient();
        $this->clearProducts();

        $client->request(
            'GET',
            '/api/products/00000000-0000-0000-0000-000000000000'
        );

        $this->assertResponseStatusCodeSame(404);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($data);
        $this->assertSame('Product not found', $data['error'] ?? null);
    }

        public function testUpdateProductPartialPriceSuccess(): void
    {
        $client = static::createClient();
        $this->clearProducts();

        $em = $this->getEntityManager();

        $product = new Product();
        $product
            ->setName('Old Name')
            ->setPrice(100.0)
            ->setSku('PROD-OLDN-12345678');

        $em->persist($product);
        $em->flush();

        $payload = [
            'price' => 150.0,
        ];

        $client->request(
            'PUT',
            '/api/products/' . $product->getIdAsString(),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame('Old Name', $data['name']);
        $this->assertEquals(150.0, $data['price']);
        $this->assertSame('PROD-OLDN-12345678', $data['sku']);
        $this->assertArrayHasKey('updatedAt', $data);
        $this->assertNotNull($data['updatedAt']);
    }

        public function testUpdateProductPartialNameSuccess(): void
    {
        $client = static::createClient();
        $this->clearProducts();

        $em = $this->getEntityManager();

        $product = new Product();
        $product
            ->setName('Old Name')
            ->setPrice(200.0)
            ->setSku('PROD-OLDN-12345678');

        $em->persist($product);
        $em->flush();

        $payload = [
            'name' => 'New Name',
        ];

        $client->request(
            'PUT',
            '/api/products/' . $product->getIdAsString(),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame('New Name', $data['name']);

        $this->assertEquals(200.0, $data['price']);

        $this->assertSame('PROD-OLDN-12345678', $data['sku']);

        $this->assertArrayHasKey('updatedAt', $data);
        $this->assertNotNull($data['updatedAt']);
    }

        public function testUpdateProductWithoutFieldsReturnsBadRequest(): void
    {
        $client = static::createClient();
        $this->clearProducts();

        $em = $this->getEntityManager();

        $product = new Product();
        $product
            ->setName('Old Name')
            ->setPrice(100.0)
            ->setSku('PROD-OLDN-12345678');

        $em->persist($product);
        $em->flush();

        $payload = [];

        $client->request(
            'PUT',
            '/api/products/' . $product->getIdAsString(),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($data);
        $this->assertSame(
            'At least one field (name or price) must be provided.',
            $data['error'] ?? null
        );
    }

        public function testUpdateProductNotFound(): void
    {
        $client = static::createClient();
        $this->clearProducts();

        $payload = [
            'name' => 'New Name',
            'price' => 200.0,
        ];

        $client->request(
            'PUT',
            '/api/products/00000000-0000-0000-0000-000000000000',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(404);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($data);
        $this->assertSame('Product not found', $data['error'] ?? null);
    }


}