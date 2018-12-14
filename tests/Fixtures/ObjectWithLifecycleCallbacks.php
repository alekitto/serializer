<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Exclude;
use Kcs\Serializer\Annotation\PostDeserialize;
use Kcs\Serializer\Annotation\PostSerialize;
use Kcs\Serializer\Annotation\PreSerialize;
use Kcs\Serializer\Annotation\Type;

/**
 * @AccessType("property")
 */
class ObjectWithLifecycleCallbacks
{
    /**
     * @Exclude
     */
    private $firstname;

    /**
     * @Exclude
     */
    private $lastname;

    /**
     * @Type("string")
     */
    private $name;

    public function __construct($firstname = 'Foo', $lastname = 'Bar')
    {
        $this->firstname = $firstname;
        $this->lastname = $lastname;
    }

    /**
     * @PreSerialize
     */
    public function prepareForSerialization()
    {
        $this->name = $this->firstname.' '.$this->lastname;
    }

    /**
     * @PostSerialize
     */
    public function cleanUpAfterSerialization()
    {
        $this->name = null;
    }

    /**
     * @PostDeserialize
     */
    public function afterDeserialization()
    {
        list($this->firstname, $this->lastname) = \explode(' ', $this->name);
        $this->name = null;
    }
}
