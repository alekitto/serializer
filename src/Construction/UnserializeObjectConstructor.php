<?php declare(strict_types=1);

namespace Kcs\Serializer\Construction;

use Doctrine\Instantiator\Instantiator;
use Kcs\Serializer\DeserializationContext;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;

class UnserializeObjectConstructor implements ObjectConstructorInterface
{
    /**
     * @var Instantiator
     */
    private $instantiator;

    public function construct(VisitorInterface $visitor, ClassMetadata $metadata, $data, Type $type, DeserializationContext $context)
    {
        return $this->getInstantiator()->instantiate($metadata->getName());
    }

    /**
     * Gets the instantiator instance.
     *
     * @return Instantiator
     */
    private function getInstantiator(): Instantiator
    {
        if (null == $this->instantiator) {
            $this->instantiator = new Instantiator();
        }

        return $this->instantiator;
    }
}
