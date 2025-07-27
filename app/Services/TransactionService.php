<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\UserInterface;
use App\DataObjects\DataTableQueryParams;
use App\DataObjects\TransactionData;
use App\Entity\Transaction;
use Doctrine\ORM\Tools\Pagination\Paginator;

class TransactionService
{
    public function __construct(private readonly EntityManagerServiceInterface $entityManager) {}

    public function create(TransactionData $data, UserInterface $user): Transaction
    {
        $transaction = new Transaction();
        $transaction->setUser($user);

        return $this->update($transaction, $data);
    }

    public function getPaginatedTransactions(DataTableQueryParams $params): Paginator
    {
        $query = $this->entityManager
            ->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->select('t', 'c', 'r')
            ->leftJoin('t.category', 'c')
            ->leftJoin('t.receipts', 'r')
            ->setFirstResult($params->start)
            ->setMaxResults($params->length);

        $orderBy  = in_array(
            $params->orderBy,
            ['description', 'date', 'amount', 'category'],
        ) ? $params->orderBy : 'date';
        $orderDir = strtolower($params->orderDir) === 'asc' ? 'asc' : 'desc';

        if (!empty($params->searchTerm)) {
            $query
                ->where('t.description LIKE :description')
                ->setParameter('description', '%' . addcslashes($params->searchTerm, '%_') . '%');
        }

        if ($orderBy === 'category') {
            $query->orderBy('c.name', $orderDir);
        } else {
            $query->orderBy('t.' . $orderBy, $orderDir);
        }

        return new Paginator($query);
    }

    public function getById(int $id): ?Transaction
    {
        return $this->entityManager->find(Transaction::class, $id);
    }

    public function update(Transaction $transaction, TransactionData $data): Transaction
    {
        $transaction->setDescription($data->description);
        $transaction->setDate($data->date);
        $transaction->setAmount($data->amount);
        $transaction->setCategory($data->category);

        return $transaction;
    }

    public function toggleReviewed(Transaction $transaction): void
    {
        $transaction->setReviewed(!$transaction->wasReviewed());
    }

    public function getTotals(\DateTime $startDate, \DateTime $endDate): array
    {
        // TODO: Implement

        return ['net' => 800, 'income' => 3000, 'expense' => 2200];
    }

    public function getRecentTransactions(int $limit): array
    {
        return $this->entityManager->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->orderBy('t.date', 'desc')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function getMonthlySummary(int $year): array
    {
        // TODO: Implement

        return [
            ['income' => 1500, 'expense' => 1100, 'm' => '3'],
            ['income' => 2000, 'expense' => 1800, 'm' => '4'],
            ['income' => 2500, 'expense' => 1900, 'm' => '5'],
            ['income' => 2600, 'expense' => 1950, 'm' => '6'],
            ['income' => 3000, 'expense' => 2200, 'm' => '7'],
        ];
    }
}