<?php declare(strict_types=1);
/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Copyright 2017 Alessandro Chitolina <alekitto@gmail.com>
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

namespace Kcs\Serializer\Tests\Fixtures\DoctrinePHPCR;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Kcs\Serializer\Annotation\Groups;
use Kcs\Serializer\Annotation\SerializedName;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\XmlAttribute;
use Kcs\Serializer\Annotation\XmlList;
use Kcs\Serializer\Annotation\XmlRoot;

/**
 * @PHPCRODM\Document
 * @XmlRoot("blog-post")
 */
class BlogPost
{
    /**
     * @PHPCRODM\Id()
     */
    protected $id;

    /**
     * @PHPCRODM\String()
     * @Groups({"comments","post"})
     */
    private $title;

    /**
     * @PHPCRODM\String()
     */
    protected $slug;

    /**
     * @PHPCRODM\Date()
     * @XmlAttribute
     */
    private $createdAt;

    /**
     * @PHPCRODM\Boolean()
     * @Type("integer")
     * This boolean to integer conversion is one of the few changes between this
     * and the standard BlogPost class. It's used to test the override behavior
     * of the DoctrineTypeDriver so notice it, but please don't change it.
     *
     * @SerializedName("is_published")
     * @Groups({"post"})
     * @XmlAttribute
     */
    private $published;

    /**
     * @PHPCRODM\ReferenceMany(targetDocument="Comment", property="blogPost")
     * @XmlList(inline=true, entry="comment")
     * @Groups({"comments"})
     */
    private $comments;

    /**
     * @PHPCRODM\ReferenceOne(targetDocument="Author")
     * @Groups({"post"})
     */
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
