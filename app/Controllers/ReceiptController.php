<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\RequestValidatorFactoryInterface;
use App\Entity\Receipt;
use App\Entity\Transaction;
use App\RequestValidators\Transaction\UploadReceiptRequestValidator;
use App\Services\ReceiptFileService;
use App\Services\ReceiptService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Psr7\Stream;

class ReceiptController
{
    public function __construct(
        private readonly RequestValidatorFactoryInterface $requestValidatorFactory,
        private readonly ReceiptService                   $receiptService,
        private readonly EntityManagerServiceInterface    $entityManagerService,
        private readonly ReceiptFileService               $receiptFileService,
    ) {}

    public function store(Request $request, Response $response, Transaction $transaction): Response
    {
        /** @var UploadedFileInterface $file */
        $file = $this->requestValidatorFactory->make(UploadReceiptRequestValidator::class)->validate(
            $request->getUploadedFiles(),
        )['receipt'];

        $filename = $file->getClientFilename();

        $randomFilename = bin2hex(random_bytes(25));

        $this->receiptFileService->store($randomFilename, $file);

        $this->entityManagerService->sync(
            $this->receiptService->create($transaction, $filename, $randomFilename, $file->getClientMediaType()),
        );

        return $response;
    }

    public function download(Response $response, Transaction $transaction, Receipt $receipt): Response
    {
        if ($receipt->getTransaction()->getId() !== $transaction->getId()) {
            return $response->withStatus(401);
        }

        $file = $this->receiptFileService->get($receipt->getStorageFilename());

        $response = $response->withHeader(
            'Content-Disposition',
            'inline; filename="' . $receipt->getFilename() . '"',
        )->withHeader('Content-Type', $receipt->getMediaType());

        return $response->withBody(new Stream($file));
    }

    public function delete(Response $response, Transaction $transaction, Receipt $receipt): Response
    {
        if ($receipt->getTransaction()->getId() !== $transaction->getId()) {
            return $response->withStatus(401);
        }

        $this->receiptFileService->delete($receipt->getStorageFilename());

        $this->entityManagerService->delete($receipt, true);

        return $response;
    }
}