<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Kcs\Metadata\ClassMetadataInterface;
use Kcs\Metadata\Loader\LoaderInterface;
use Kcs\Serializer\Metadata\AdditionalPropertyMetadata;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Metadata\StaticPropertyMetadata;
use Kcs\Serializer\Metadata\VirtualPropertyMetadata;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type as SymfonyType;

/**
 * This class decorates any other driver. If the inner driver does not provide a
 * a property type, the decorator will guess based on symfony property info component.
 */
class PropertyInfoTypeLoader implements LoaderInterface
{
    protected LoaderInterface $delegate;
    private PropertyInfoExtractorInterface $propertyInfoExtractor;

    public function __construct(LoaderInterface $delegate, PropertyInfoExtractorInterface $propertyInfoExtractor)
    {
        $this->delegate = $delegate;
        $this->propertyInfoExtractor = $propertyInfoExtractor;
    }

    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        /* @var $classMetadata ClassMetadata */
        $this->delegate->loadClassMetadata($classMetadata);

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

            // Virtual property or derived property: type should not be guessed.
            if (
                $propertyMetadata instanceof VirtualPropertyMetadata ||
                $propertyMetadata instanceof StaticPropertyMetadata ||
                $propertyMetadata instanceof AdditionalPropertyMetadata
            ) {
                continue;
            }

            $types = $this->propertyInfoExtractor->getTypes($classMetadata->name, $propertyMetadata->name);
            if (null === $types || 1 !== \count($types)) {
                continue;
            }

            $type = \reset($types);
            if ($type->isCollection()) {
                $params = [];

                $keyType = $type->getCollectionKeyType();
                if (null !== $keyType && SymfonyType::BUILTIN_TYPE_INT !== $keyType->getBuiltinType()) {
                    $params[] = $keyType->getClassName() ?? $keyType->getBuiltinType();
                }

                $valueType = $type->getCollectionValueType();
                if (null !== $valueType) {
                    $params[] = $valueType->getClassName() ?? $valueType->getBuiltinType();
                }

                $type = $type->getClassName() ?? $type->getBuiltinType();
                $type .= $params ? '<'.\implode(',', $params).'>' : '';
            } else {
                $type = $type->getClassName() ?? $type->getBuiltinType();
            }

            $propertyMetadata->setType($type);
        }

        return true;
    }
}
