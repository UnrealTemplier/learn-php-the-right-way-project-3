<?php

declare(strict_types=1);

namespace App\Services;

use League\Flysystem\Filesystem;
use Psr\Http\Message\UploadedFileInterface;

class ReceiptFileService
{
    const RECEIPT_FILES_PATH = 'receipts/';

    public function __construct(private readonly Filesystem $filesystem) {}

    public function store(string $filename, UploadedFileInterface $file): void
    {
        $this->filesystem->write($this::RECEIPT_FILES_PATH . $filename, $file->getStream()->getContents());
    }

    public function get(string $filename)
    {
        return $this->filesystem->readStream($this::RECEIPT_FILES_PATH . $filename);
    }

    public function delete(string $filename): void
    {
        $this->filesystem->delete($this::RECEIPT_FILES_PATH . $filename);
    }
}