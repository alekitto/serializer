<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\DoctrinePHPCR;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCRODM;

#[PHPCRODM\Document]
class Comment
{
    #[PHPCRODM\Id]
    protected $id;

    #[PHPCRODM\ReferenceOne(targetDocument: Author::class)]
    private $author;

    #[PHPCRODM\ReferenceOne(targetDocument: BlogPost::class)]
    private $blogPost;

    #[PHPCRODM\Field(type: 'string')]
    private $text;

    public function __construct(Author $author, $text)
    {
        $this->author = $author;
        $this->text = $text;
        $this->blogPost = new ArrayCollection();
    }

    public function getAuthor()
    {
        return $this->author;
    }
}
