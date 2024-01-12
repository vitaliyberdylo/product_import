<?php

namespace App\Tests\Serializer\Normalizer;

use App\Entity\Category;
use App\Entity\Product;
use App\Serializer\Normalizer\CategoryDenormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class CategoryDenormalizerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private CategoryDenormalizer $denormalizer;

    public function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->denormalizer = new CategoryDenormalizer($this->entityManager);
    }

    /**
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization(mixed $data, string $type)
    {
        $this->assertTrue($this->denormalizer->supportsDenormalization($data, $type));
    }

    public function supportsDenormalizationDataProvider(): array
    {
        return [
            'Category type with data' => [
                'data' => 'Toys',
                'type' => Category::class,
            ],
            'Category type without data' => [
                'data' => null,
                'type' => Category::class,
            ],
        ];
    }

    public function testNotSupportsDenormalization()
    {
        $this->assertFalse($this->denormalizer->supportsDenormalization('Toys', Product::class));
    }

    public function testGetSupportedTypes()
    {
        $this->assertEquals(
            [
                Category::class => true,
            ],
            $this->denormalizer->getSupportedTypes(null)
        );
    }

    public function testDenormalize()
    {
        $categoryName = 'Toys';
        $categoryId = 42;
        $category = $this->createCategoryStub($categoryId);

        $categoryRepository = $this->createMock(EntityRepository::class);
        $categoryRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $categoryName])
            ->willReturn($category);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($categoryRepository);

        $this->entityManager->expects($this->never())
            ->method('persist');
        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->entityManager->expects($this->once())
            ->method('getReference')
            ->with(Category::class, $categoryId)
            ->willReturn($category);

        $this->assertEquals($category, $this->denormalizer->denormalize($categoryName, Category::class));
    }

    private function createCategoryStub(int $id): Category
    {
        $category = new Category();
        $reflectionClass = new \ReflectionClass(Category::class);
        $reflectionClass->getProperty('id')->setValue($category, $id);

        return $category;
    }
}
