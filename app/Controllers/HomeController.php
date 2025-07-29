<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\RequestValidatorFactoryInterface;
use App\RequestValidators\GetYearStatsRequestValidator;
use App\ResponseFormatter;
use App\Services\CategoryService;
use App\Services\TransactionService;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

class HomeController
{
    public function __construct(
        private readonly Twig                             $twig,
        private readonly TransactionService               $transactionService,
        private readonly CategoryService                  $categoryService,
        private readonly ResponseFormatter                $responseFormatter,
        private readonly RequestValidatorFactoryInterface $requestValidatorFactory,
    ) {}

    public function index(Response $response): Response
    {
        return $this->twig->render(
            $response,
            'dashboard.twig',
            [
                'transactions' => $this->transactionService->getRecentTransactions(10)
            ]
        );
    }

    public function getYearStats(Response $response, array $args): Response
    {
        $year = (int)$this->requestValidatorFactory->make(GetYearStatsRequestValidator::class)->validate($args)['year'];
        return $this->responseFormatter->asJson($response, $this->transactionService->getTotals($year));
    }

    public function getYearChart(Response $response, array $args): Response
    {
        $year = (int)$this->requestValidatorFactory->make(GetYearStatsRequestValidator::class)->validate($args)['year'];
        return $this->responseFormatter->asJson($response, $this->transactionService->getMonthlySummary($year));
    }

    public function getTopSpendingCategories(Response $response): Response
    {
        return $this->responseFormatter->asJson($response, $this->categoryService->getTopSpendingCategories(4));
    }
}
