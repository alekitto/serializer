<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;

/**
 * @AccessType("property")
 */
class Comment
{
    /**
     * @Type("Kcs\Serializer\Tests\Fixtures\Author")
     */
    private $author;

    /**
     * @Type("string")
     */
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
