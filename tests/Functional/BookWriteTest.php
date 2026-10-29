<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Test\Client;
use App\Factory\BookFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class BookWriteTest extends ApiTestCase
{
    use ClockSensitiveTrait;

    private const CLIENT_CREATED_AT = '2026-10-26 08:00:00';

    private Client $client;

    public function setUp(): void {
        parent::setUp();
        $this->client = self::createClient();

    }

    public function testPostBookIgnoresClientDates(): void
    {
        $mockTime = self::mockTime('2026-10-26 09:41:22');

        $response = $this->client->request(
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
        $mockTime = self::mockTime('2026-10-26 09:41:22');
        $bookCreationDate = $mockTime->now();
        $book = BookFactory::createOne([
            'createdAt' => $bookCreationDate,
        ]);
        $iri = $this->getIriFromResource($book);

        $mockTime->sleep(2);

        $response = $this->client->request(
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
        $response = $this->client->request(
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

    #[DataProvider('arrayProvider')]
    public function testPostRejectsInvalidPayload(
        array $data,
        string $expectedPropertyPath,
    ): void
    {
        $response = $this->client->request(
            'POST',
            '/api/books',
            [
                'headers' => [
                    'content-type' => 'application/ld+json',
                ],
                'json' => $data,
            ],
        );

        self::assertResponseStatusCodeSame(422);

        $violations = $response->toArray(false)['violations'];
        self::assertContains(
            $expectedPropertyPath,
            array_column($violations, 'propertyPath'),
        );
    }

    public static function arrayProvider(): \Generator
    {
        yield 'missing title' => [
            'data' => [
                'isbn' => '978-2-25303146-8',
                'publishedAt' => '2026-10-27T09:41:22.589Z',
                'author' => 'Author of book',
            ],
            'expectedPropertyPath' => 'title',
        ];

        yield 'empty title' => [
            'data' => [
                'isbn' => '978-2-25303146-8',
                'publishedAt' => '2026-10-27T09:41:22.589Z',
                'title' => '',
                'author' => 'Author of book',
            ],
            'expectedPropertyPath' => 'title',
        ];

        yield 'too long title' => [
            'data' => [
                'isbn' => '978-2-25303146-8',
                'publishedAt' => '2026-10-27T09:41:22.589Z',
                'title' => str_repeat('a', 256),
                'author' => 'Author of book',
            ],
            'expectedPropertyPath' => 'title',
        ];

        yield 'empty author' => [
            'data' => [
                'isbn' => '978-2-25303146-8',
                'publishedAt' => '2026-10-27T09:41:22.589Z',
                'title' => 'Book title',
                'author' => '',
            ],
            'expectedPropertyPath' => 'author',
        ];

        yield 'publishedAt null' => [
            'data' => [
                'isbn' => '978-2-25303146-8',
                'publishedAt' => null,
                'title' => 'Book title',
                'author' => 'Author of book',
            ],
            'expectedPropertyPath' => 'publishedAt',
        ];

        yield 'invalid isbn' => [
            'data' => [
                'isbn' => '9782-25303146-545e',
                'publishedAt' => '2026-10-27T09:41:22.589Z',
                'title' => 'Book title',
                'author' => 'Author of book',
            ],
            'expectedPropertyPath' => 'isbn',
        ];
    }
}
