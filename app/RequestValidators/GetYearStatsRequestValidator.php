<?php

declare(strict_types=1);

namespace App\RequestValidators;

use App\Contracts\RequestValidatorInterface;
use App\Exception\ValidationException;
use Valitron\Validator;

class GetYearStatsRequestValidator implements RequestValidatorInterface
{
    public function validate(array $data): array
    {
        $v = new Validator($data);
        $v->rule('required', ['year']);
        $v->rule('integer', ['year']);

        if (!$v->validate()) {
            throw new ValidationException($v->errors());
        }

        return $data;
    }
}