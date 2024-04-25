<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute as Serializer;
use Kcs\Serializer\Metadata\Access;

#[Serializer\AccessType(Access\Type::Property)]
class ObjectWithEnums
{
    private PostType $postType = PostType::POST_TEXT;
    private ObjectType $objectType = ObjectType::OBJECT_SECOND;
    private NonTypedObjectType $nonTypedObjectType = NonTypedObjectType::OBJECT_FIRST_NT;
}
