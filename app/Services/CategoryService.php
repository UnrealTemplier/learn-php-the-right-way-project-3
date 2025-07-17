<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\UserInterface;
use App\Entity\Category;
use Doctrine\ORM\EntityManager;

class CategoryService
{
    public function __construct(private readonly EntityManager $entityManager) {}

    public function create(string $name, UserInterface $user): Category
    {
        $category = new Category();
        $category->setName($name);
        $category->setUser($user);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    public function delete(int $id): void
    {
        $this->entityManager->remove($this->entityManager->find(Category::class, $id));
        $this->entityManager->flush();
    }

    public function getAll(): array
    {
        return $this->entityManager->getRepository(Category::class)->findAll();
    }

    public function getById(int $id): ?Category
    {
        return $this->entityManager->find(Category::class, $id);
    }
}