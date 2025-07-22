<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Receipt;
use App\Entity\Transaction;

class ReceiptService extends EntityManagerService
{
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

        $this->entityManager->persist($receipt);

        return $receipt;
    }

    public function getById(int $id): ?Receipt
    {
        return $this->entityManager->find(Receipt::class, $id);
    }

    public function delete(int $id): void
    {
        $this->entityManager->remove($this->entityManager->find(Receipt::class, $id));
    }
}