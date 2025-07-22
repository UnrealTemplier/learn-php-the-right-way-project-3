<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\EntityManagerServiceInterface;
use App\Entity\Category;
use Bezhanov\Faker\Provider\Commerce;
use Faker\Factory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PopulateDbWithFakesController
{
    public function __construct(private readonly EntityManagerServiceInterface $entityManagerService) {}

    public function populateCategories(Request $request, Response $response): Response
    {
        $faker = Factory::create();
        $faker->addProvider(new Commerce($faker));

        $user = $request->getAttribute('user');

        for ($i = 0; $i < 10; $i++) {
            $category = new Category();
            $category->setUser($user);
            $category->setName($faker->unique()->word());
            $this->entityManagerService->persist($category);
        }

        $this->entityManagerService->sync();

        return $response;
    }
}