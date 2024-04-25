<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\SerializedName;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml;
use Kcs\Serializer\Metadata\Access;

#[Xml\Root("log")]
#[AccessType(Access\Type::Property)]
class Log
{
    #[SerializedName("author_list")]
    #[Xml\Map]
    #[Type("AuthorList")]
    private $authors;

    #[Xml\XmlList(entry: "comment", inline: true)]
    #[Type("array<".Comment::class.">")]
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
