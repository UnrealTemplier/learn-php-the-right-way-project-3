<?php

declare(strict_types=1);

namespace App\RequestValidators\Transaction;

use App\Contracts\RequestValidatorInterface;
use App\Exception\ValidationException;
use App\Services\CategoryService;
use Valitron\Validator;

class TransactionRequestValidator implements RequestValidatorInterface
{
    public function __construct(private readonly CategoryService $categoryService) {}

    public function validate(array $data): array
    {
        $v = new Validator($data);
        $v->rule('required', ['description', 'date', 'amount', 'category']);
        $v->rule('lengthMax', 'description', 255);
        $v->rule('dateFormat', 'Y-m-d\TH:i');
        $v->rule('numeric', 'amount');
        $v->rule('integer', 'category');
        $v->rule(function ($field, $value, array $params, array $fields) use (&$data) {
            $categoryId = (int)$value;

            if (!$categoryId || !($category = $this->categoryService->getById($categoryId))) {
                return false;
            }

            $data['category'] = $category;

            return true;
        }, 'category')->message('Category not found');

        if (!$v->validate()) {
            throw new ValidationException($v->errors());
        }

        return $data;
    }
}