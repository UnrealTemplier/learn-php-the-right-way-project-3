<?php

declare(strict_types=1);

namespace App\RequestValidators\Category;

use App\Contracts\RequestValidatorInterface;
use App\Exception\ValidationException;
use Valitron\Validator;

class UpdateCategoryRequestValidator implements RequestValidatorInterface
{
    public function validate(array $data): array
    {
        $v = new Validator($data);
        $v->rule('required', ['name']);
        $v->rule('lengthMax', 'name', 50);

        if (!$v->validate()) {
            throw new ValidationException($v->errors());
        }

        return $data;
    }
}