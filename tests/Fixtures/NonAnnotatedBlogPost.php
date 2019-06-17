<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Groups;
use Kcs\Serializer\Annotation\OnExclude;
use Kcs\Serializer\Annotation\SerializedName;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\XmlAttribute;
use Kcs\Serializer\Annotation\XmlElement;
use Kcs\Serializer\Annotation\XmlList;
use Kcs\Serializer\Annotation\XmlMap;
use Kcs\Serializer\Annotation\XmlNamespace;
use Kcs\Serializer\Annotation\XmlRoot;
use PhpCollection\Map;
use PhpCollection\Sequence;

/**
 * @XmlRoot("blog-post")
 * @XmlNamespace(uri="http://example.com/namespace")
 * @XmlNamespace(uri="http://schemas.google.com/g/2005", prefix="gd")
 * @XmlNamespace(uri="http://www.w3.org/2005/Atom", prefix="atom")
 * @XmlNamespace(uri="http://purl.org/dc/elements/1.1/", prefix="dc")
 * @AccessType("property")
 */
class NonAnnotatedBlogPost
{
    /**
     * @XmlElement(cdata=false)
     * @Groups({"comments","post"})
     */
    private $id = 'what_a_nice_id';

    /**
     * @Groups({"comments","post"})
     * @XmlElement(namespace="http://purl.org/dc/elements/1.1/");
     * @OnExclude("skip")
     */
    private $title;

    /**
     * @var \DateTimeInterface
     *
     * @XmlAttribute
     */
    private $createdAt;

    /**
     * @SerializedName("is_published")
     * @XmlAttribute
     * @Groups({"post"})
     */
    private $published;

    /**
     * @var string
     *
     * @XmlAttribute(namespace="http://schemas.google.com/g/2005")
     * @Groups({"post"})
     */
    private $etag;

    /**
     * @var ArrayCollection<Comment>
     *
     * @XmlList(inline=true, entry="comment")
     * @Groups({"comments"})
     */
    private $comments;

    /**
     * @var Sequence<Comment>
     *
     * @XmlList(inline=true, entry="comment2")
     * @Groups({"comments"})
     */
    private $comments2;

    /**
     * @var Map<string,string>
     *
     * @XmlMap(keyAttribute = "key")
     */
    private $metadata;

    /**
     * @var Author
     *
     * @Groups({"post"})
     * @XmlElement(namespace="http://www.w3.org/2005/Atom")
     */
    private $author;

    /**
     * @var string
     *
     * @Type("Kcs\Serializer\Tests\Fixtures\Publisher")
     */
    private $publisher;

    /**
     * @var array<Tag>
     *
     * @XmlList(inline=true, entry="tag", namespace="http://purl.org/dc/elements/1.1/");
     */
    private $tag;

    public function __construct(string $title, Author $author, \DateTime $createdAt, Publisher $publisher)
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

    public function isPublished(): bool
    {
        return $this->published;
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
