<?php

namespace App\Factory;

use App\Entity\Book;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Book>
 *
 * @method static Book createOne(array $attributes = [])
 * @method static list<Book> createMany(int $number, array|callable $attributes = [])
 */
final class BookFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Book::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'isbn' => self::faker()->unique()->isbn13(),
            'author' => self::faker()->firstName() . ' ' . self::faker()->lastName(),
            'publishedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'title' => self::faker()->sentence(4),
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Book $book): void {})
        ;
    }
}
