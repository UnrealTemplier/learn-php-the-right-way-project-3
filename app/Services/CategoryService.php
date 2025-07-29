<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\UserInterface;
use App\DataObjects\DataTableQueryParams;
use App\Entity\Category;
use App\Entity\Transaction;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

class CategoryService
{
    public function __construct(private readonly EntityManagerServiceInterface $entityManager) {}

    public function create(string $name, UserInterface $user): Category
    {
        $category = new Category();
        $category->setUser($user);

        return $this->update($category, $name);
    }

    public function getPaginatedCategories(DataTableQueryParams $params): Paginator
    {
        $query = $this->getQueryBuilder()
                      ->setFirstResult($params->start)
                      ->setMaxResults($params->length);

        $orderBy  = in_array($params->orderBy, ['name', 'createdAt', 'updatedAt']) ? $params->orderBy : 'updatedAt';
        $orderDir = strtolower($params->orderDir) === 'asc' ? 'asc' : 'desc';

        if (!empty($params->searchTerm)) {
            $query->where('c.name LIKE :name')->setParameter(
                'name',
                '%' . addcslashes($params->searchTerm, '%_') . '%',
            );
        }

        $query->orderBy('c.' . $orderBy, $orderDir);

        return new Paginator($query);
    }

    public function getById(int $id): ?Category
    {
        return $this->entityManager->find(Category::class, $id);
    }

    public function update(Category $category, string $name): Category
    {
        $category->setName($name);

        return $category;
    }

    public function getCategoryNames(): array
    {
        return $this->getQueryBuilder()
                    ->select('c.id', 'c.name')
                    ->getQuery()
                    ->getArrayResult();
    }

    public function findByName(string $name): ?Category
    {
        return $this->entityManager->getRepository(Category::class)->findBy(['name' => $name])[0] ?? null;
    }

    public function getAllKeyedByName(): array
    {
        $categories  = $this->entityManager->getRepository(Category::class)->findAll();
        $categoryMap = [];

        foreach ($categories as $category) {
            $categoryMap[strtolower($category->getName())] = $category;
        }

        return $categoryMap;
    }

    public function getTopSpendingCategories(int $limit): array
    {
        return $this->getQueryBuilder()
            ->select('c.name AS name')
            ->addSelect('SUM(CASE WHEN t.amount > 0 THEN t.amount ELSE 0 END) as total')
            ->leftJoin(Transaction::class, 't', 'WITH', 't.category = c')
            ->groupBy('c.id')
            ->orderBy('total', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    private function getQueryBuilder(): QueryBuilder
    {
        return $this->entityManager->getRepository(Category::class)->createQueryBuilder('c');
    }
}