<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Book;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class BookProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly ClockInterface $clock,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Book) {
            throw new \LogicException(
                sprintf(
                    'Expected %s, got %s',
                    Book::class,
                    get_debug_type($data),
                )
            );
        }

        if ($operation instanceof Post) {
            $data->setCreatedAt($this->clock->now());
        }

        if ($operation instanceof Patch) {
            $data->setUpdatedAt($this->clock->now());
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
