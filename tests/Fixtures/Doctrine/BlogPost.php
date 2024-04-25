<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Kcs\Serializer\Attribute\Groups;
use Kcs\Serializer\Attribute\SerializedName;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Attribute\Xml\Attribute;
use Kcs\Serializer\Attribute\Xml\Root;
use Kcs\Serializer\Attribute\Xml\XmlList;

#[ORM\Entity]
#[Root('blog-post')]
class BlogPost
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    protected $id;

    #[ORM\Column(type: Types::STRING)]
    #[Groups(['comments', 'post'])]
    private $title;

    #[ORM\Column(type: 'some_custom_type')]
    protected $slug;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Attribute]
    private $createdAt;

    /**
     * This boolean to integer conversion is one of the few changes between this
     * and the standard BlogPost class. It's used to test the override behavior
     * of the DoctrineTypeDriver so notice it, but please don't change it.
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    #[Type('integer')]
    #[SerializedName('is_published')]
    #[Groups(['post'])]
    #[Attribute]
    private $published;

    #[ORM\OneToMany(mappedBy: 'blogPost', targetEntity: Comment::class)]
    #[XmlList(entry: 'comment', inline: true)]
    #[Groups(['comments'])]
    private $comments;

    #[ORM\OneToOne(targetEntity: Author::class)]
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
