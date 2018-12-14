<?php declare(strict_types=1);

namespace Kcs\Serializer\Exception;

use Symfony\Component\Validator\ConstraintViolationList;

class ValidationFailedException extends RuntimeException
{
    /**
     * @var ConstraintViolationList
     */
    private $list;

    public function __construct(ConstraintViolationList $list)
    {
        parent::__construct(\sprintf('Validation failed with %d error(s).', \count($list)));

        $this->list = $list;
    }

    public function getConstraintViolationList(): ConstraintViolationList
    {
        return $this->list;
    }
}
