<?php

declare(strict_types=1);

namespace Kcs\Serializer\Exception;

use Symfony\Component\Validator\ConstraintViolationList;

use function count;
use function sprintf;

class ValidationFailedException extends RuntimeException
{
    public function __construct(private ConstraintViolationList $list)
    {
        parent::__construct(sprintf('Validation failed with %d error(s).', count($list)));
    }

    public function getConstraintViolationList(): ConstraintViolationList
    {
        return $this->list;
    }
}
