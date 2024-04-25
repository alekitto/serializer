<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\Accessor;
use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Immutable;
use Kcs\Serializer\Annotation\Xml;
use Kcs\Serializer\Metadata\Access;

#[Xml\Root('post')]
class IndexedCommentsBlogPost
{
    #[Xml\Map(entry: 'comments', inline: true, keyAttribute: 'author-name')]
    #[Accessor(getter: 'getCommentsIndexedByAuthor')]
    #[Immutable]
    private $comments = [];

    public function __construct()
    {
        $author = new Author('Foo');
        $this->comments[] = new Comment($author, 'foo');
        $this->comments[] = new Comment($author, 'bar');
    }

    public function getCommentsIndexedByAuthor()
    {
        $indexedComments = [];
        foreach ($this->comments as $comment) {
            $authorName = $comment->getAuthor()->getName();

            if (! isset($indexedComments[$authorName])) {
                $indexedComments[$authorName] = new IndexedCommentsList();
            }

            $indexedComments[$authorName]->addComment($comment);
        }

        return $indexedComments;
    }
}

#[AccessType(Access\Type::Property)]
class IndexedCommentsList
{
    #[Xml\XmlList(entry: 'comment', inline: true)]
    private $comments = [];

    #[Xml\Attribute]
    private $count = 0;

    public function addComment(Comment $comment)
    {
        $this->comments[] = $comment;
        ++$this->count;
    }
}
