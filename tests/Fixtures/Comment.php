<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;

/**
 * @AccessType("property")
 */
#[AccessType(AccessType::PROPERTY)]
class Comment
{
    /**
     * @Type("Kcs\Serializer\Tests\Fixtures\Author")
     */
    #[Type(Author::class)]
    private $author;

    /**
     * @Type("string")
     */
    #[Type('string')]
    private $text;

    public function __construct(Author $author = null, $text)
    {
        $this->author = $author;
        $this->text = $text;
    }

    public function getAuthor()
    {
        return $this->author;
    }
}
