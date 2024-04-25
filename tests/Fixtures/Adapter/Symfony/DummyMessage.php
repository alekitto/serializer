<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Adapter\Symfony;

use Kcs\Serializer\Attribute as Serializer;
use Kcs\Serializer\Metadata\Access\Type;


#[Serializer\AccessType(Type::Property)]
class DummyMessage implements DummyMessageInterface
{
    public function __construct(
        /** @var string */
        #[Serializer\Type('string')]
        #[Serializer\Groups(['foo'])]
        private readonly string $message,
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
