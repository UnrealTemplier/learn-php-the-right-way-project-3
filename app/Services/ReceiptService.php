<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\Entity\Receipt;
use App\Entity\Transaction;

class ReceiptService
{
    public function __construct(private readonly EntityManagerServiceInterface $entityManager) {}

    public function create(
        Transaction $transaction,
        string      $filename,
        string      $storageFilename,
        string      $mediaType,
    ): Receipt {
        $receipt = new Receipt();

        $receipt->setFilename($filename);
        $receipt->setStorageFilename($storageFilename);
        $receipt->setMediaType($mediaType);
        $receipt->setTransaction($transaction);
        $receipt->setCreatedAt(new \DateTime());

        return $receipt;
    }

    public function getById(int $id): ?Receipt
    {
        return $this->entityManager->find(Receipt::class, $id);
    }
}