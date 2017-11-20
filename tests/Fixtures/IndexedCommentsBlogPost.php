<?php declare(strict_types=1);
/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Copyright 2016 Alessandro Chitolina <alekitto@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\Accessor;
use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\ReadOnly;
use Kcs\Serializer\Annotation\XmlAttribute;
use Kcs\Serializer\Annotation\XmlList;
use Kcs\Serializer\Annotation\XmlMap;
use Kcs\Serializer\Annotation\XmlRoot;

/** @XmlRoot("post") */
class IndexedCommentsBlogPost
{
    /**
     * @XmlMap(keyAttribute="author-name", inline=true, entry="comments")
     * @Accessor(getter="getCommentsIndexedByAuthor")
     * @ReadOnly()
     */
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

/**
 * @AccessType("property")
 */
class IndexedCommentsList
{
    /** @XmlList(inline=true, entry="comment") */
    private $comments = [];

    /** @XmlAttribute */
    private $count = 0;

    public function addComment(Comment $comment)
    {
        $this->comments[] = $comment;
        $this->count += 1;
    }
}
