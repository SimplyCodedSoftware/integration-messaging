<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\Blog;
use SimplyCodedSoftware\DomainModel\AggregateNotFoundException;
use SimplyCodedSoftware\DomainModel\AggregateRepository;

/**
 * Class InMemoryArticleRepository
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\Blog
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryArticleRepository implements AggregateRepository
{
    /**
     * @var Article[]
     */
    private $articles;

    /**
     * InMemoryArticleRepository constructor.
     * @param Article[] $articles
     */
    private function __construct(array $articles)
    {
        foreach ($articles as $article) {
            $this->save($article);
        }
    }

    /**
     * @return InMemoryArticleRepository
     */
    public static function createEmpty() : self
    {
        return new self([]);
    }

    /**
     * @param array $articles
     * @return InMemoryArticleRepository
     */
    public static function createWith(array $articles) : self
    {
        return new self($articles);
    }

    /**
     * @inheritDoc
     */
    public function findBy(array $identifiers)
    {
        $identifier = $identifiers['author'] . $identifiers['title'];

        if (!array_key_exists($identifier, $this->articles)) {
            throw AggregateNotFoundException::create("Article with id {$identifier} doesn't exists having: " . implode(",", array_keys($this->articles)));
        }

        return $this->articles[$identifier];
    }

    /**
     * @inheritDoc
     */
    public function findWithLockingBy(array $identifiers, int $expectedVersion)
    {
        return $this->findBy($identifiers);
    }

    /**
     * @inheritDoc
     */
    public function canHandle(string $aggregateClassName): bool
    {
        return $aggregateClassName === Article::class;
    }

    /**
     * @param Article $aggregate
     */
    public function save($aggregate): void
    {
        $this->articles[$this->getIdentifier($aggregate)] = $aggregate;
    }

    /**
     * @param $aggregate
     * @return string
     */
    private function getIdentifier(Article $aggregate): string
    {
        return $aggregate->getAuthor() . $aggregate->getTitle();
    }
}