<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\Table;

#[Entity, Table('receipts')]
class Receipt
{
    #[Id, Column(options: ['unsigned' => true]), GeneratedValue]
    private int $id;

    #[Column(name: 'filename')]
    private string $filename;

    #[Column(name: 'storage_filename')]
    private string $storageFilename;

    #[Column(name: 'media_type')]
    private string $media_type;

    #[Column(name: 'created_at')]
    private \DateTime $createdAt;

    #[ManyToOne(inversedBy: 'transactions')]
    private Transaction $transaction;

    #[PrePersist, PreUpdate]
    public function updateCreatedAt(LifecycleEventArgs $args): void
    {
        if (!isset($this->createdAt)) {
            $this->createdAt = new \DateTime();
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): Receipt
    {
        $this->filename = $filename;
        return $this;
    }

    public function getStorageFilename(): string
    {
        return $this->storageFilename;
    }

    public function setStorageFilename(string $storageFilename): Receipt
    {
        $this->storageFilename = $storageFilename;
        return $this;
    }

    public function getMediaType(): string
    {
        return $this->media_type;
    }

    public function setMediaType(string $media_type): Receipt
    {
        $this->media_type = $media_type;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): Receipt
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(Transaction $transaction): Receipt
    {
        $transaction->addReceipt($this);
        $this->transaction = $transaction;
        return $this;
    }
}
