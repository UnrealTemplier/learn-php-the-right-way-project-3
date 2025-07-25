<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\RequestValidatorFactoryInterface;
use App\DataObjects\TransactionData;
use App\Entity\Receipt;
use App\Entity\Transaction;
use App\RequestValidators\Transaction\TransactionRequestValidator;
use App\ResponseFormatter;
use App\Services\CategoryService;
use App\Services\ReceiptFileService;
use App\Services\RequestService;
use App\Services\TransactionService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class TransactionsController
{
    public function __construct(
        private readonly Twig                             $twig,
        private readonly RequestValidatorFactoryInterface $requestValidatorFactory,
        private readonly TransactionService               $transactionService,
        private readonly CategoryService                  $categoryService,
        private readonly ResponseFormatter                $responseFormatter,
        private readonly RequestService                   $requestService,
        private readonly EntityManagerServiceInterface    $entityManagerService,
        private readonly ReceiptFileService               $receiptFileService,
    ) {}

    public function index(Response $response): Response
    {
        return $this->twig->render(
            $response,
            'transactions/index.twig',
            [
                'categories' => $this->categoryService->getCategoryNames(),
            ],
        );
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $this->requestValidatorFactory->make(TransactionRequestValidator::class)->validate(
            $request->getParsedBody(),
        );

        $this->entityManagerService->sync(
            $this->transactionService->create(
                new TransactionData(
                    $data['description'],
                    (float)$data['amount'],
                    new \DateTime($data['date']),
                    $data['category'],
                ),
                $request->getAttribute('user'),
            ),
        );

        return $response;
    }

    public function delete(Response $response, Transaction $transaction): Response
    {
        /** @var Receipt[] $receipts */
        $receipts = $transaction->getReceipts();

        foreach ($receipts as $receipt) {
            $this->receiptFileService->delete($receipt->getStorageFilename());
        }

        $this->entityManagerService->delete($transaction, true);

        return $response;
    }

    public function get(Response $response, Transaction $transaction): Response
    {
        $data = [
            'id'          => $transaction->getId(),
            'description' => $transaction->getDescription(),
            'date'        => $transaction->getDate()->format('Y-m-d\TH:i'),
            'amount'      => $transaction->getAmount(),
            'category'    => $transaction->getCategory()?->getId(),
        ];

        return $this->responseFormatter->asJson($response, $data);
    }

    public function update(Request $request, Response $response, Transaction $transaction): Response
    {
        $data = $this->requestValidatorFactory->make(TransactionRequestValidator::class)->validate(
            $request->getParsedBody(),
        );

        $this->entityManagerService->sync(
            $this->transactionService->update(
                $transaction,
                new TransactionData(
                    $data['description'],
                    (float)$data['amount'],
                    new \DateTime($data['date']),
                    $data['category'],
                ),
            ),
        );

        return $response;
    }

    public function load(Request $request, Response $response): Response
    {
        $params = $this->requestService->getDataTableQueryParameters($request);

        $transactions = $this->transactionService->getPaginatedTransactions($params);

        $transformer = function (Transaction $transaction) {
            return [
                'id'          => $transaction->getId(),
                'description' => $transaction->getDescription(),
                'date'        => $transaction->getDate()->format('m/d/Y g:i A'),
                'amount'      => $transaction->getAmount(),
                'category'    => $transaction->getCategory()?->getName(),
                'wasReviewed' => $transaction->wasReviewed(),
                'receipts'    => $transaction->getReceipts()->map(fn(Receipt $receipt)
                    => [
                    'name' => $receipt->getFilename(),
                    'id'   => $receipt->getId(),
                ])->toArray(),
            ];
        };

        $totalTransactions = count($transactions);

        return $this->responseFormatter->asDataTable(
            $response,
            array_map($transformer, (array)$transactions->getIterator()),
            $params->draw,
            $totalTransactions,
        );
    }

    public function toggleReviewed(Response $response, Transaction $transaction): Response
    {
        $this->transactionService->toggleReviewed($transaction);
        $this->entityManagerService->sync();

        return $response;
    }
}
