<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\UserInterface;
use App\DataObjects\DataTableQueryParams;
use App\DataObjects\TransactionData;
use App\Entity\Transaction;
use Doctrine\ORM\QueryBuilder;
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
        $query = $this->getQueryBuilder()
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

    public function getTotals(int $year): array
    {
        return $this->getQueryBuilderForYear($year)
                    ->select('SUM(t.amount) as net')
                    ->addSelect('SUM(CASE WHEN t.amount > 0 THEN t.amount ELSE 0 END) as income')
                    ->addSelect('SUM(CASE WHEN t.amount < 0 THEN ABS(t.amount) ELSE 0 END) as expense')
                    ->getQuery()
                    ->getOneOrNullResult();
    }

    public function getRecentTransactions(int $limit): array
    {
        return $this->getQueryBuilder()
                    ->orderBy('t.date', 'desc')
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getArrayResult();
    }

    public function getMonthlySummary(int $year): array
    {
        return $this->getQueryBuilderForYear($year)
                    ->select('MONTH(t.date) as m')
                    ->addSelect('SUM(CASE WHEN t.amount > 0 THEN t.amount ELSE 0 END) as income')
                    ->addSelect('SUM(CASE WHEN t.amount < 0 THEN ABS(t.amount) ELSE 0 END) as expense')
                    ->groupBy('m')
                    ->orderBy('m', 'asc')
                    ->getQuery()
                    ->getArrayResult();
    }

    private function getQueryBuilder(): QueryBuilder
    {
        return $this->entityManager->getRepository(Transaction::class)->createQueryBuilder('t');
    }

    private function getQueryBuilderForYear(int $year): QueryBuilder
    {
        return $this->getQueryBuilder()
                    ->where('YEAR(t.date) = :year')
                    ->setParameter('year', $year);
    }
}