<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\DoctrinePHPCR;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCRODM;
use Kcs\Serializer\Attribute\Groups;
use Kcs\Serializer\Attribute\SerializedName;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Attribute\Xml\Attribute;
use Kcs\Serializer\Attribute\Xml\XmlList;
use Kcs\Serializer\Attribute\Xml\Root;

#[PHPCRODM\Document]
#[Root('blog-post')]
class BlogPost
{
    #[PHPCRODM\Id]
    protected $id;

    #[PHPCRODM\Field(type: "string")]
    #[Groups(['comments', 'post'])]
    private $title;

    #[PHPCRODM\Field(type: "string")]
    protected $slug;

    #[PHPCRODM\Field(type: "date")]
    #[Attribute]
    private $createdAt;

    /**
     * This boolean to integer conversion is one of the few changes between this
     * and the standard BlogPost class. It's used to test the override behavior
     * of the DoctrineTypeDriver so notice it, but please don't change it.
     */
    #[PHPCRODM\Field(type: "boolean")]
    #[Type('integer')]
    #[SerializedName('is_published')]
    #[Groups(['post'])]
    #[Attribute]
    private $published;

    #[PHPCRODM\ReferenceMany(property: "blogPost", targetDocument: Comment::class)]
    #[XmlList(entry: 'comment', inline: true)]
    #[Groups(['comments'])]
    private $comments;

    #[PHPCRODM\ReferenceOne(targetDocument: Author::class)]
    #[Groups(['post'])]
    private $author;

    public function __construct($title, Author $author, \DateTime $createdAt)
    {
        $this->title = $title;
        $this->author = $author;
        $this->published = false;
        $this->comments = new ArrayCollection();
        $this->createdAt = $createdAt;
    }

    public function setPublished()
    {
        $this->published = true;
    }

    public function addComment(Comment $comment)
    {
        $this->comments->add($comment);
    }
}
