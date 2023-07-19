<?php

declare(strict_types=1);

namespace Kcs\Serializer\Construction;

use Doctrine\Instantiator\Instantiator;
use Kcs\Serializer\DeserializationContext;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;

class UnserializeObjectConstructor implements ObjectConstructorInterface
{
    private Instantiator|null $instantiator = null;

    public function construct(VisitorInterface $visitor, ClassMetadata $metadata, mixed $data, Type $type, DeserializationContext $context): object
    {
        return $this->getInstantiator()->instantiate($metadata->getName());
    }

    /**
     * Gets the instantiator instance.
     */
    private function getInstantiator(): Instantiator
    {
        if ($this->instantiator === null) {
            $this->instantiator = new Instantiator();
        }

        return $this->instantiator;
    }
}
