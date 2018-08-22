<?php declare(strict_types=1);

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
        ++$this->count;
    }
}
