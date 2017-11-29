<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\PostDeserialize;
use Kcs\Serializer\Annotation\Type;

/**
 * @AccessType("property")
 */
class CircularReferenceParent
{
    /** @Type("array<Kcs\Serializer\Tests\Fixtures\CircularReferenceChild>") */
    protected $collection = [];

    /** @Type("ArrayCollection<Kcs\Serializer\Tests\Fixtures\CircularReferenceChild>") */
    private $anotherCollection;

    public function __construct()
    {
        $this->collection[] = new CircularReferenceChild('child1', $this);
        $this->collection[] = new CircularReferenceChild('child2', $this);

        $this->anotherCollection = new ArrayCollection();
        $this->anotherCollection->add(new CircularReferenceChild('child1', $this));
        $this->anotherCollection->add(new CircularReferenceChild('child2', $this));
    }

    /** @PostDeserialize */
    public function afterDeserialization()
    {
        if (! $this->collection) {
            $this->collection = [];
        }
        foreach ($this->collection as $v) {
            $v->setParent($this);
        }

        if (! $this->anotherCollection) {
            $this->anotherCollection = new ArrayCollection();
        }
        foreach ($this->anotherCollection as $v) {
            $v->setParent($this);
        }
    }
}
