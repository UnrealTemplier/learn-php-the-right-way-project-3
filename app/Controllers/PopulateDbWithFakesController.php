<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Entity\Category;
use Bezhanov\Faker\Provider\Commerce;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PopulateDbWithFakesController
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    public function populateCategories(Request $request, Response $response): Response
    {
        $faker = Factory::create();
        $faker->addProvider(new Commerce($faker));

        $user = $request->getAttribute('user');

        for ($i = 0; $i < 10; $i++) {
            $category = new Category();
            $category->setUser($user);
            $category->setName($faker->unique()->word());
            $this->entityManager->persist($category);
        }

        $this->entityManager->flush();

        return $response;
    }
}