<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;
use App\Entity\Book;
use App\Factory\BookFactory;

final class BookReadTest extends ApiTestCase
{
    public function testGetBookReturnsItemWithoutId(): void
    {
        $client = self::createClient();
        $book = BookFactory::createOne();
        $iri = $this->getIriFromResource($book);
        $response = $client->request(
            'GET',
            $iri,
        );

        self::assertResponseStatusCodeSame(200);
        self::assertJsonContains([
            '@context' => '/api/contexts/Book',
            '@id' => $iri,
            '@type' => 'Book',
            'isbn' => $book->getIsbn(),
            'title' => $book->getTitle(),
            'author' => $book->getAuthor(),
            'publishedAt' => $book->getPublishedAt()->format(\DateTimeInterface::ATOM),
        ]);
        self::assertMatchesResourceItemJsonSchema(Book::class);
        self::assertArrayNotHasKey('id', $response->toArray());
    }

    public function testGetBooksReturnsCollectionWithoutId(): void
    {
        $client = self::createClient();
        $books = BookFactory::createMany(5);

        $response = $client->request(
            'GET',
            '/api/books',
        );

        self::assertResponseStatusCodeSame(200);
        self::assertJsonContains([
            '@context' => '/api/contexts/Book',
            '@id' => '/api/books',
            '@type' => 'Collection',
            'totalItems' => 5,
        ]);
        self::assertMatchesResourceCollectionJsonSchema(Book::class);

        $member = $response->toArray()['member'];
        self::assertCount(5, $member);

        $membersByIri = array_column($member, null, '@id');
        $book = $books[0];
        $iri = $this->getIriFromResource($book);

        self::assertArrayHasKey($iri, $membersByIri);

        $bookData = $membersByIri[$iri];
        self::assertSame('Book', $bookData['@type']);
        self::assertSame($book->getIsbn(), $bookData['isbn']);
        self::assertSame($book->getTitle(), $bookData['title']);
        self::assertSame($book->getAuthor(), $bookData['author']);
        self::assertSame($book->getPublishedAt()->format(\DateTimeInterface::ATOM), $bookData['publishedAt']);
        self::assertArrayNotHasKey('id', $bookData);
    }

    public function testGetBooksFirstPageIsLimitedTo30Items(): void
    {
        $client = self::createClient();
        BookFactory::createMany(35);

        $response = $client->request(
            'GET',
            '/api/books',
        );

        self::assertResponseStatusCodeSame(200);
        self::assertMatchesResourceCollectionJsonSchema(Book::class);

        self::assertJsonContains([
            '@context' => '/api/contexts/Book',
            '@id' => '/api/books',
            '@type' => 'Collection',
            'totalItems' => 35,
        ]);

        $result = $response->toArray();

        $member = $result['member'];
        self::assertCount(30, $member);

        $view = $result['view'];
        self::assertArrayNotHasKey('previous', $view);

        self::assertJsonContains([
            'view' => [
                '@id' => '/api/books?page=1',
                '@type' => 'PartialCollectionView',
                'first' => '/api/books?page=1',
                'last' => '/api/books?page=2',
                'next' => '/api/books?page=2',
            ],
        ]);
    }

    public function testGetBooksLastPageContainsRemainingItems(): void
    {
        $client = self::createClient();
        BookFactory::createMany(35);

        $response = $client->request(
            'GET',
            '/api/books',
            [
                'query' => ['page' => 2],
            ],
        );

        self::assertResponseStatusCodeSame(200);
        self::assertMatchesResourceCollectionJsonSchema(Book::class);

        self::assertJsonContains([
            '@context' => '/api/contexts/Book',
            '@id' => '/api/books',
            '@type' => 'Collection',
            'totalItems' => 35,
        ]);

        $result = $response->toArray();

        $member = $result['member'];
        self::assertCount(5, $member);

        $view = $result['view'];
        self::assertArrayNotHasKey('next', $view);

        self::assertJsonContains([
            'view' => [
                '@id' => '/api/books?page=2',
                '@type' => 'PartialCollectionView',
                'first' => '/api/books?page=1',
                'last' => '/api/books?page=2',
                'previous' => '/api/books?page=1',
            ],
        ]);
    }
}
