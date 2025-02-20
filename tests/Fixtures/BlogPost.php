<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Groups;
use Kcs\Serializer\Annotation\OnExclude;
use Kcs\Serializer\Annotation\SerializedName;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml;
use PhpCollection\Map;
use PhpCollection\Sequence;

/**
 * @Xml\Root("blog-post")
 * @Xml\XmlNamespace(uri="http://example.com/namespace")
 * @Xml\XmlNamespace(uri="http://schemas.google.com/g/2005", prefix="gd")
 * @Xml\XmlNamespace(uri="http://www.w3.org/2005/Atom", prefix="atom")
 * @Xml\XmlNamespace(uri="http://purl.org/dc/elements/1.1/", prefix="dc")
 * @AccessType("property")
 */
class BlogPost
{
    /**
     * @Type("string")
     * @Xml\Element(cdata=false)
     * @Groups({"comments","post"})
     */
    private $id = 'what_a_nice_id';

    /**
     * @Type("string")
     * @Groups({"comments","post"})
     * @Xml\Element(namespace="http://purl.org/dc/elements/1.1/");
     * @OnExclude("skip")
     */
    private $title;

    /**
     * @Type("DateTime")
     * @Xml\Attribute
     */
    private $createdAt;

    /**
     * @Type("boolean")
     * @SerializedName("is_published")
     * @Xml\Attribute
     * @Groups({"post"})
     */
    private $published;

    /**
     * @Type("string")
     * @Xml\Attribute(namespace="http://schemas.google.com/g/2005")
     * @Groups({"post"})
     */
    private $etag;

    /**
     * @Type("ArrayCollection<Kcs\Serializer\Tests\Fixtures\Comment>")
     * @Xml\XmlList(inline=true, entry="comment")
     * @Groups({"comments"})
     */
    private $comments;

    /**
     * @Type("PhpCollection\Sequence<Kcs\Serializer\Tests\Fixtures\Comment>")
     * @Xml\XmlList(inline=true, entry="comment2")
     * @Groups({"comments"})
     */
    private $comments2;

    /**
     * @Type("PhpCollection\Map<string,string>")
     * @Xml\Map(keyAttribute = "key")
     */
    private $metadata;

    /**
     * @Type("Kcs\Serializer\Tests\Fixtures\Author")
     * @Groups({"post"})
     * @Xml\Element(namespace="http://www.w3.org/2005/Atom")
     */
    private $author;

    /**
     * @Type("Kcs\Serializer\Tests\Fixtures\Publisher")
     */
    private $publisher;

    /**
     * @Type("array<Kcs\Serializer\Tests\Fixtures\Tag>")
     * @Xml\XmlList(inline=true, entry="tag", namespace="http://purl.org/dc/elements/1.1/");
     */
    private $tag;

    public function __construct($title, Author $author, \DateTime $createdAt, Publisher $publisher)
    {
        $this->title = $title;
        $this->author = $author;
        $this->publisher = $publisher;
        $this->published = false;
        $this->comments = new ArrayCollection();
        $this->comments2 = new Sequence();
        $this->metadata = new Map();
        $this->metadata->set('foo', 'bar');
        $this->createdAt = $createdAt;
        $this->etag = \sha1($this->createdAt->format(\DateTime::ISO8601));
    }

    public function setPublished()
    {
        $this->published = true;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function addComment(Comment $comment)
    {
        $this->comments->add($comment);
        $this->comments2->add($comment);
    }

    public function addTag(Tag $tag)
    {
        $this->tag[] = $tag;
    }
}
