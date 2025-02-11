<?php

namespace App\Controller\Api\Csv\Exception;

use App\Exception\Api as ApiException;

class InvalidInput extends ApiException {

    public int $statusCode = 400;
}
