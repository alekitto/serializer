<?php

declare(strict_types=1);

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
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\CompositeTypeInterface;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeIdentifier;

use function assert;
use function count;
use function implode;
use function method_exists;
use function reset;

/**
 * This class decorates any other driver. If the inner driver does not provide a
 * a property type, the decorator will guess based on symfony property info component.
 */
class PropertyInfoTypeLoader implements LoaderInterface
{
    public function __construct(protected LoaderInterface $delegate, private PropertyInfoExtractorInterface $propertyInfoExtractor)
    {
    }

    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        assert($classMetadata instanceof ClassMetadata);
        $this->delegate->loadClassMetadata($classMetadata);

        // We base our scan on the internal driver's property list so that we
        // respect any internal white/blacklisting like in the AnnotationDriver
        foreach ($classMetadata->getAttributesMetadata() as $key => $propertyMetadata) {
            if (! $propertyMetadata instanceof PropertyMetadata) {
                continue;
            }

            // If the inner driver provides a type, don't guess anymore.
            if ($propertyMetadata->type !== null) {
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

            if (method_exists($this->propertyInfoExtractor, 'getTypes')) { // sf < 8
                $types = $this->propertyInfoExtractor->getTypes($classMetadata->name, $propertyMetadata->name);
                if ($types === null || count($types) !== 1) {
                    continue;
                }

                $type = reset($types);

                if ($type->isCollection()) {
                    $params = [];
                    $keyType = $type->getCollectionKeyTypes()[0] ?? null;
                    /* @phpstan-ignore-next-line */
                    if ($keyType !== null && $keyType->getBuiltinType() !== SymfonyType::BUILTIN_TYPE_INT) {
                        $params[] = $keyType->getClassName() ?? $keyType->getBuiltinType();
                    }

                    $valueType = $type->getCollectionValueTypes()[0] ?? null;
                    if ($valueType !== null) {
                        $params[] = $valueType->getClassName() ?? $valueType->getBuiltinType();
                    }

                    $type = $type->getClassName() ?? $type->getBuiltinType();
                    $type .= $params ? '<' . implode(',', $params) . '>' : '';
                } else {
                    $type = $type->getClassName() ?? $type->getBuiltinType();
                }
            } else {
                $type = $this->propertyInfoExtractor->getType($classMetadata->name, $propertyMetadata->name);
                if ($type === null) {
                    continue;
                }

                if ($type instanceof CompositeTypeInterface) {
                    continue;
                }

                if ($type instanceof CollectionType) {
                    $params = [];
                    $mainType = $type->getWrappedType();
                    if ($mainType instanceof GenericType) {
                        $mainType = $mainType->getWrappedType();
                    }

                    if (! $type->isList()) {
                        $keyType = $type->getCollectionKeyType();
                        if ($keyType instanceof BuiltinType && $keyType->getTypeIdentifier() !== TypeIdentifier::INT) {
                            $params[] = $keyType->getTypeIdentifier()->value;
                        } elseif ($keyType instanceof ObjectType) {
                            $params[] = $keyType->getClassName();
                        } else {
                            continue;
                        }
                    }

                    $valueType = $type->getCollectionValueType();
                    if ($valueType instanceof BuiltinType) {
                        $params[] = $valueType->getTypeIdentifier()->value;
                    } elseif ($valueType instanceof ObjectType) {
                        $params[] = $valueType->getClassName();
                    } else {
                        continue;
                    }

                    $type = $mainType . '<' . implode(',', $params) . '>';
                } else {
                    $type = (string) $type;
                }
            }

            $propertyMetadata->setType($type);
        }

        return true;
    }
}
