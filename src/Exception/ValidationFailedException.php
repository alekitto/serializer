<?php

declare(strict_types=1);

namespace Kcs\Serializer\Exception;

use Symfony\Component\Validator\ConstraintViolationList;

use function count;
use function Safe\sprintf;

class ValidationFailedException extends RuntimeException
{
    private ConstraintViolationList $list;

    public function __construct(ConstraintViolationList $list)
    {
        parent::__construct(sprintf('Validation failed with %d error(s).', count($list)));

        $this->list = $list;
    }

    public function getConstraintViolationList(): ConstraintViolationList
    {
        return $this->list;
    }
}
