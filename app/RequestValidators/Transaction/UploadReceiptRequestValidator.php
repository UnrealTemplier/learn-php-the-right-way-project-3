<?php

declare(strict_types=1);

namespace App\RequestValidators\Transaction;

use App\Contracts\RequestValidatorInterface;
use App\Exception\ValidationException;
use finfo;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Psr\Http\Message\UploadedFileInterface;

class UploadReceiptRequestValidator implements RequestValidatorInterface
{
    public function validate(array $data): array
    {
        /** @var UploadedFileInterface $uploadedFile */
        $uploadedFile = $data['receipt'] ?? null;

        $this->validateFileIsUploaded($uploadedFile);

        $this->validateFileSize($uploadedFile);

        $this->validateFilename($uploadedFile);

        $this->validateFileType($uploadedFile);

        return $data;
    }

    private function validateFileIsUploaded(?UploadedFileInterface $uploadedFile): void
    {
        if (!$uploadedFile) {
            throw new ValidationException(['receipt' => ['Please select a receipt file']]);
        }

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            throw new ValidationException(['receipt' => ['Failed to upload a receipt file']]);
        }
    }

    private function validateFileSize(UploadedFileInterface $uploadedFile): void
    {
        $maxFileSize = 5 * 1024 * 1024;
        if ($uploadedFile->getSize() > $maxFileSize) {
            throw new ValidationException(['receipt' => ['Maximum allowed size if 5 MB']]);
        }
    }

    private function validateFilename(UploadedFileInterface $uploadedFile): void
    {
        $filename = $uploadedFile->getClientFilename();
        if (!preg_match('/^[a-zA-Zа-яА-Я0-9\s._-]+$/', $filename)) {
            throw new ValidationException(['receipt' => ['Invalid filename']]);
        }
    }

    private function validateFileType(UploadedFileInterface $uploadedFile): void
    {
        $allowedMimeTypes  = ['image/jpeg', 'image/png', 'application/pdf'];
        $tmpFilePath       = $uploadedFile->getStream()->getMetadata('uri');

        if (!in_array($uploadedFile->getClientMediaType(), $allowedMimeTypes)) {
            throw new ValidationException(['receipt' => ['Receipt has to be either an image or a pdf document']]);
        }

        $detector = new FinfoMimeTypeDetector();
        $mimeType = $detector->detectMimeTypeFromFile($tmpFilePath);

        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new ValidationException(['receipt' => ['Invalid file type']]);
        }
    }
}