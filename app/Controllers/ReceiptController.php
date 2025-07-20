<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\RequestValidatorFactoryInterface;
use App\RequestValidators\Transaction\UploadReceiptRequestValidator;
use League\Flysystem\Filesystem;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;

class ReceiptController
{
    public function __construct(
        private readonly Filesystem                       $filesystem,
        private readonly RequestValidatorFactoryInterface $requestValidatorFactory,
    ) {}

    public function store(Request $request, Response $response, array $args): Response
    {
        /** @var UploadedFileInterface $file */
        $file     = $this->requestValidatorFactory->make(UploadReceiptRequestValidator::class)->validate(
            $request->getUploadedFiles(),
        );
        //$filename = $file->getClientFilename();
        //
        //$this->filesystem->write('receipts/' . $filename, $file->getStream()->getContents());

        return $response;
    }
}