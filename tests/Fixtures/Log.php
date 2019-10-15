<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\SerializedName;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml;

/**
 * @Xml\Root("log")
 * @AccessType("property")
 */
class Log
{
    /**
     * @SerializedName("author_list")
     * @Xml\Map
     * @Type("AuthorList")
     */
    private $authors;

    /**
     * @Xml\XmlList(inline=true, entry = "comment")
     * @Type("array<Kcs\Serializer\Tests\Fixtures\Comment>")
     */
    private $comments;

    public function __construct()
    {
        $this->authors = new AuthorList();
        $this->authors->add(new Author('Johannes Schmitt'));
        $this->authors->add(new Author('John Doe'));

        $author = new Author('Foo Bar');
        $this->comments = [];
        $this->comments[] = new Comment($author, 'foo');
        $this->comments[] = new Comment($author, 'bar');
        $this->comments[] = new Comment($author, 'baz');
    }
}
