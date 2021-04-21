<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Metadata\Loader;

use Doctrine\Common\Annotations\AnnotationReader;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\Loader\AnnotationLoader;
use Kcs\Serializer\Metadata\Loader\ReflectionLoader;
use Kcs\Serializer\Tests\Fixtures\Entity_74_Proxy;
use Kcs\Serializer\Tests\Fixtures\Entity_UnionType;
use Kcs\Serializer\Type\Type;
use PHPUnit\Framework\TestCase;

class ReflectionLoaderTest extends TestCase
{
    private ReflectionLoader $loader;

    protected function setUp(): void
    {
        $loader = new AnnotationLoader();
        $loader->setReader(new AnnotationReader());
        $this->loader = new ReflectionLoader($loader);
    }

    public function testShouldLoadTypesFromTypedProperties(): void
    {
        $m = new ClassMetadata(new \ReflectionClass(Entity_74_Proxy::class));
        $this->loader->loadClassMetadata($m);

        self::assertEquals(Type::from('string'), $m->getAttributeMetadata('notUnset')->type);
        self::assertEquals(Type::from('string'), $m->getAttributeMetadata('nullableString')->type);
        self::assertEquals(Type::from('string'), $m->getAttributeMetadata('virtualProperty')->type);
    }

    /**
     * @requires PHP 8.0
     */
    public function testShouldNotLoadTypesFromTypedPropertiesWithUnionType(): void
    {
        $m = new ClassMetadata(new \ReflectionClass(Entity_UnionType::class));
        $this->loader->loadClassMetadata($m);

        self::assertNull($m->getAttributeMetadata('uninitialized')->type);
    }
}
