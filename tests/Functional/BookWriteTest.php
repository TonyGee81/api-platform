<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;
use App\Factory\BookFactory;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class BookWriteTest extends ApiTestCase
{
    use ClockSensitiveTrait;

    private const CLIENT_CREATED_AT = '2026-10-26 08:00:00';

    public function testPostBookIgnoresClientDates(): void
    {
        $client = self::createClient();
        $mockTime = self::mockTime('2026-10-26 09:41:22');

        $response = $client->request(
            'POST',
            '/api/books',
            [
                'headers' => [
                    'content-type' => 'application/ld+json',
                ],
                'json' => [
                    'isbn' => '978-2-25303146-8',
                    'publishedAt' => '2026-10-27T09:41:22.589Z',
                    'createdAt' => self::CLIENT_CREATED_AT,
                    'updatedAt' => self::CLIENT_CREATED_AT,
                    'title' => 'Book title',
                    'author' => 'Author of book'
                ],
            ],
        );

        self::assertResponseStatusCodeSame(201);

        $content = $response->toArray();
        self::assertArrayNotHasKey('updatedAt', $content);
        self::assertSame($mockTime->now()->format(DATE_ATOM), $content['createdAt']);
    }

    public function testPatchBookIgnoresClientDates(): void
    {
        $client = self::createClient();
        $mockTime = self::mockTime('2026-10-26 09:41:22');
        $bookCreationDate = $mockTime->now();
        $book = BookFactory::createOne([
            'createdAt' => $bookCreationDate,
        ]);
        $iri = $this->getIriFromResource($book);

        $mockTime->sleep(2);

        $response = $client->request(
            'PATCH',
            $iri,
            [
                'headers' => [
                    'content-type' => 'application/merge-patch+json',
                ],
                'json' => [
                    'createdAt' => self::CLIENT_CREATED_AT,
                    'updatedAt' => self::CLIENT_CREATED_AT,
                ],
            ],
        );

        self::assertResponseIsSuccessful();

        $content = $response->toArray();
        self::assertSame($bookCreationDate->format(DATE_ATOM), $content['createdAt']);
        self::assertSame($mockTime->now()->format(DATE_ATOM), $content['updatedAt']);
    }

    public function testPostCreatesBookAndReturnsLocation(): void
    {
        $client = self::createClient();

        $response = $client->request(
            'POST',
            '/api/books',
            [
                'headers' => [
                    'content-type' => 'application/ld+json',
                ],
                'json' => [
                    'isbn' => '978-2-25303146-8',
                    'publishedAt' => '2026-10-27T09:41:22.589Z',
                    'title' => 'Book title',
                    'author' => 'Author of book'
                ],
            ],
        );

        self::assertResponseStatusCodeSame(201);
        $content = $response->toArray();
        BookFactory::repository()->assert()->count(
            1,
            [
                'isbn' => '9782253031468',
                'title' => 'Book title',
                'author' => 'Author of book',
            ]
        );

        self::assertResponseHeaderSame('location', $content['@id']);
    }
}
