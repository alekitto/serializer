<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Adapter\Symfony;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Groups;
use Kcs\Serializer\Annotation\Type;

/**
 * @AccessType("property")
 */
class DummyMessage implements DummyMessageInterface
{
    /**
     * @var string
     *
     * @Type("string")
     * @Groups({"foo"})
     */
    private $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
