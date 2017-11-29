<?php declare(strict_types=1);

namespace Kcs\Serializer\Construction;

use Kcs\Serializer\DeserializationContext;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;

/**
 * Implementations of this interface construct new objects during deserialization.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface ObjectConstructorInterface
{
    /**
     * Constructs a new object.
     *
     * Implementations could for example create a new object calling "new", use
     * "unserialize" techniques, reflection, or other means.
     *
     * @param VisitorInterface       $visitor
     * @param ClassMetadata          $metadata
     * @param mixed                  $data
     * @param Type                   $type
     * @param DeserializationContext $context
     *
     * @return object
     */
    public function construct(VisitorInterface $visitor, ClassMetadata $metadata, $data, Type $type, DeserializationContext $context);
}
