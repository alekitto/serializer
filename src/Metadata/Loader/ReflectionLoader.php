<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Kcs\Metadata\ClassMetadataInterface;
use Kcs\Metadata\Loader\LoaderInterface;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Metadata\VirtualPropertyMetadata;

class ReflectionLoader implements LoaderInterface
{
    private LoaderInterface $delegate;

    public function __construct(LoaderInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        $ret = $this->delegate->loadClassMetadata($classMetadata);
        if (! $ret || PHP_VERSION_ID < 70400) {
            return $ret;
        }

        // We base our scan on the internal driver's property list so that we
        // respect any internal white/blacklisting like in the AnnotationDriver
        foreach ($classMetadata->getAttributesMetadata() as $key => $propertyMetadata) {
            if (! $propertyMetadata instanceof PropertyMetadata) {
                continue;
            }

            // If the inner driver provides a type, don't guess anymore.
            if (null !== $propertyMetadata->type) {
                continue;
            }

            if ($propertyMetadata instanceof VirtualPropertyMetadata) {
                $this->loadVirtualProperty($propertyMetadata);
                continue;
            }

            try {
                $reflectionProperty = $propertyMetadata->getReflection();
            } catch (\ReflectionException $e) {
                continue;
            }

            if (! $reflectionProperty->hasType()) {
                continue;
            }

            $propertyMetadata->setType($reflectionProperty->getType()->getName());
        }

        return true;
    }

    private function loadVirtualProperty(VirtualPropertyMetadata $propertyMetadata): void
    {
        try {
            $reflection = new \ReflectionMethod($propertyMetadata->class, $propertyMetadata->getter);
        } catch (\ReflectionException $e) {
            return;
        }

        if (! $reflection->hasReturnType()) {
            return;
        }

        $type = $reflection->getReturnType();
        if (null === $type || 'void' === $type->getName()) {
            return;
        }

        $propertyMetadata->setType($type->getName());
    }
}
