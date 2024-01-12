<?php

namespace App\Serializer\Normalizer;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class CategoryDenormalizer implements DenormalizerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): mixed
    {
        $categoryRepository = $this->entityManager->getRepository(Category::class);
        $category = $categoryRepository->findOneBy(['name' => $data]);
        if (!$category) {
            $category = (new Category())->setName($data);
            $this->entityManager->persist($category);
            $this->entityManager->flush($category);
        }

        return $this->entityManager->getReference(Category::class, $category->getId());
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $type === Category::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Category::class => true,
        ];
    }
}
