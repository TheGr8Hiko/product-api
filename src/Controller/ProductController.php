<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CreateProductDto;
use App\Dto\UpdateProductDto;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\SkuGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/products', name: 'api_products_')]
class ProductController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository,
        private SkuGenerator $skuGenerator,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json([
                'error' => 'Invalid JSON payload',
            ], 400);
        }

        $dto = new CreateProductDto();
        $dto->name = $data['name'] ?? null;
        $dto->price = isset($data['price']) ? (float) $data['price'] : null;

        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json([
                'errors' => $this->formatValidationErrors($errors),
            ], 400);
        }

        $product = new Product();
        $product
            ->setName($dto->name)
            ->setPrice($dto->price)
            ->setSku($this->skuGenerator->generate($dto->name));

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->json(
            $this->serializeProduct($product),
            201
        );
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (!$product instanceof Product) {
            return $this->json([
                'error' => 'Product not found',
            ], 404);
        }

        return $this->json($this->serializeProduct($product));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (!$product instanceof Product) {
            return $this->json([
                'error' => 'Product not found',
            ], 404);
        }

        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json([
                'error' => 'Invalid JSON payload',
            ], 400);
        }

        $dto = new UpdateProductDto();
        $dto->name = $data['name'] ?? null;
        $dto->price = isset($data['price']) ? (float) $data['price'] : null;

        // If nothing is provided, return a clear error
        if ($dto->name === null && $dto->price === null) {
            return $this->json([
                'error' => 'At least one field (name or price) must be provided.',
            ], 400);
        }

        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json([
                'errors' => $this->formatValidationErrors($errors),
            ], 400);
        }

        if ($dto->name !== null)  $product->setName($dto->name);
        if ($dto->price !== null) $product->setPrice($dto->price);
        $product->setUpdatedAt(new \DateTimeImmutable());

        // Important: we keep the same SKU on update
        $this->entityManager->flush();

        return $this->json($this->serializeProduct($product));
    }

    /**
     * Serialize a Product entity into an array for JSON responses.
     */
    private function serializeProduct(Product $product): array
    {
        return [
            'id' => $product->getIdAsString(),
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'price' => $product->getPrice(),
            'createdAt' => $product->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $product->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Format validation errors into a simple array for the client.
     */
    private function formatValidationErrors(iterable $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $errors[] = [
                'field' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return $errors;
    }
}
