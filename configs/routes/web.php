<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CategoriesController;
use App\Controllers\HomeController;
use App\Controllers\PopulateDbWithFakesController;
use App\Controllers\ReceiptController;
use App\Controllers\TransactionImporterController;
use App\Controllers\TransactionsController;
use App\Controllers\VerifyController;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\ValidateSignatureMiddleware;
use App\Middleware\VerifyEmailMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/', [HomeController::class, 'index']);

        $group->group('/categories', function (RouteCollectorProxy $categories) {
            $categories->get('', [CategoriesController::class, 'index']);
            $categories->get('/load', [CategoriesController::class, 'load']);
            $categories->post('', [CategoriesController::class, 'store']);
            $categories->get('/{category}', [CategoriesController::class, 'get']);
            $categories->post('/{category}', [CategoriesController::class, 'update']);
            $categories->delete('/{category}', [CategoriesController::class, 'delete']);
        });

        $group->group('/transactions', function (RouteCollectorProxy $transactions) {
            $transactions->get('', [TransactionsController::class, 'index']);
            $transactions->get('/load', [TransactionsController::class, 'load']);
            $transactions->post('/import', [TransactionImporterController::class, 'import']);
            $transactions->post('', [TransactionsController::class, 'store']);
            $transactions->get('/{transaction}', [TransactionsController::class, 'get']);
            $transactions->post('/{transaction}', [TransactionsController::class, 'update']);
            $transactions->post('/{transaction}/review', [TransactionsController::class, 'toggleReviewed']);
            $transactions->delete('/{transaction}', [TransactionsController::class, 'delete']);
            $transactions->post('/{transaction}/receipts', [ReceiptController::class, 'store']);
            $transactions->get('/{transaction}/receipts/{receipt}', [ReceiptController::class, 'download']);
            $transactions->delete('/{transaction}/receipts/{receipt}', [ReceiptController::class, 'delete']);
        });
    })->add(VerifyEmailMiddleware::class)->add(AuthMiddleware::class);

    $app->group('', function (RouteCollectorProxy $group) {
        $group->post('/logout', [AuthController::class, 'logOut']);
        $group->get('/verify', [VerifyController::class, 'index']);
        $group
            ->get('/verify/{id}/{hash}', [VerifyController::class, 'verify'])
            ->setName('verify')
            ->add(ValidateSignatureMiddleware::class);
        $group->post('/verify', [VerifyController::class, 'resend']);
    })->add(AuthMiddleware::class);

    $app->group('', function (RouteCollectorProxy $group) {
        $group->group('', function (RouteCollectorProxy $guest) {
            $guest->get('/login', [AuthController::class, 'loginView']);
            $guest->get('/register', [AuthController::class, 'registerView']);
            $guest->post('/login', [AuthController::class, 'login']);
            $guest->post('/register', [AuthController::class, 'register']);
        });
    })->add(GuestMiddleware::class);

    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/fake/categories', [PopulateDbWithFakesController::class, 'populateCategories']);
    })->add(AuthMiddleware::class);
};
