<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Metadata\Loader;

use Doctrine\Common\Annotations\AnnotationReader;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\Loader\AnnotationLoader;
use Kcs\Serializer\Metadata\Loader\ReflectionLoader;
use Kcs\Serializer\Tests\Fixtures\Entity_74_Proxy;
use Kcs\Serializer\Type\Type;
use PHPUnit\Framework\TestCase;

/**
 * @requires PHP 7.4
 */
class ReflectionLoaderTest extends TestCase
{
    /**
     * @var ReflectionLoader
     */
    private $loader;

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
}
