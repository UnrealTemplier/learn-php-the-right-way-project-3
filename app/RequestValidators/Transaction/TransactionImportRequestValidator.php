<?php

declare(strict_types=1);

namespace App\RequestValidators\Transaction;

use App\Contracts\RequestValidatorInterface;
use App\Exception\ValidationException;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Psr\Http\Message\UploadedFileInterface;

class TransactionImportRequestValidator implements RequestValidatorInterface
{
    public function validate(array $data): array
    {
        /** @var UploadedFileInterface $uploadedFile */
        $uploadedFile = $data['importFile'] ?? null;

        $this->validateFileIsUploaded($uploadedFile);

        $this->validateFileSize($uploadedFile);

        $this->validateFileType($uploadedFile);

        return $data;
    }

    private function validateFileIsUploaded(?UploadedFileInterface $uploadedFile): void
    {
        if (!$uploadedFile) {
            throw new ValidationException(['importFile' => ['Please select a file to import']]);
        }

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            throw new ValidationException(['importFile' => ['Failed to upload the file for import']]);
        }
    }

    private function validateFileSize(UploadedFileInterface $uploadedFile): void
    {
        $maxFileSize = 20 * 1024 * 1024;

        if ($uploadedFile->getSize() > $maxFileSize) {
            throw new ValidationException(['importFile' => ['Maximum allowed size is 20 MB']]);
        }
    }

    private function validateFileType(UploadedFileInterface $uploadedFile): void
    {
        $allowedMimeTypes = ['text/csv', 'application/vnd.ms-excel'];

        if (!in_array($uploadedFile->getClientMediaType(), $allowedMimeTypes)) {
            throw new ValidationException(['importFile' => ['Please select a CSV file to import']]);
        }

        $detector = new FinfoMimeTypeDetector();
        $mimeType = $detector->detectMimeTypeFromFile($uploadedFile->getStream()->getMetadata('uri'));

        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new ValidationException(['importFile' => ['Invalid file type']]);
        }
    }
}
