<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CategoriesController;
use App\Controllers\HomeController;
use App\Controllers\PopulateDbWithFakesController;
use App\Controllers\ReceiptController;
use App\Controllers\TransactionsController;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->get('/', [HomeController::class, 'index'])->add(AuthMiddleware::class);

    $app->get('/fake/categories', [PopulateDbWithFakesController::class, 'populateCategories'])->add(AuthMiddleware::class);

    $app->group('', function (RouteCollectorProxy $guest) {
        $guest->get('/login', [AuthController::class, 'loginView']);
        $guest->get('/register', [AuthController::class, 'registerView']);
        $guest->post('/login', [AuthController::class, 'login']);
        $guest->post('/register', [AuthController::class, 'register']);
    })->add(GuestMiddleware::class);

    $app->post('/logout', [AuthController::class, 'logOut'])->add(AuthMiddleware::class);

    $app->group('/categories', function (RouteCollectorProxy $categories) {
        // Render main Categories page
        $categories->get('', [CategoriesController::class, 'index']);
        // Get paginated categories data in DataTable js library format
        $categories->get('/load', [CategoriesController::class, 'load']);

        // Create new category
        $categories->post('', [CategoriesController::class, 'store']);

        // Get a specific category
        $categories->get('/{id:[0-9]+}', [CategoriesController::class, 'get']);
        // Update a specific category
        $categories->post('/{id:[0-9]+}', [CategoriesController::class, 'update']);
        // Delete a specific category
        $categories->delete('/{id:[0-9]+}', [CategoriesController::class, 'delete']);
    })->add(AuthMiddleware::class);

    $app->group('/transactions', function (RouteCollectorProxy $transactions) {
        // Render main Transactions page
        $transactions->get('', [TransactionsController::class, 'index']);
        // Get paginated transactions data in DataTable js library format
        $transactions->get('/load', [TransactionsController::class, 'load']);

        // Create new transaction
        $transactions->post('', [TransactionsController::class, 'store']);

        // Get a specific transaction
        $transactions->get('/{id:[0-9]+}', [TransactionsController::class, 'get']);
        // Update a specific transaction
        $transactions->post('/{id:[0-9]+}', [TransactionsController::class, 'update']);
        // Delete a specific transaction
        $transactions->delete('/{id:[0-9]+}', [TransactionsController::class, 'delete']);
        // Upload a receipt
        $transactions->post('/{id:[0-9]+}/receipts', [ReceiptController::class, 'store']);
    })->add(AuthMiddleware::class);
};
