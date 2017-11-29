<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\Accessor;
use Kcs\Serializer\Annotation\ReadOnly;
use Kcs\Serializer\Annotation\SerializedName;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\XmlRoot;

/**
 * @XmlRoot("author")
 * @ReadOnly
 */
class AuthorReadOnlyPerClass
{
    /**
     * @ReadOnly
     * @SerializedName("id")
     */
    private $id;

    /**
     * @Type("string")
     * @SerializedName("full_name")
     * @Accessor("getName")
     * @ReadOnly(false)
     */
    private $name;

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
