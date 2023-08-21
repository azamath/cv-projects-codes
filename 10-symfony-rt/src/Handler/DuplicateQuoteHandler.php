<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Handler;

use App\Entity\Quote;
use App\Repository\QuoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class DuplicateQuoteHandler
{
    public function __construct(private EntityManagerInterface $manager, private DenormalizerInterface $denormalizer)
    {
    }

    public function handle(int $quoteId, array $attributes): Quote
    {
        $quote = $this->getQuotesRepository()->find($quoteId);

        if (!$quote) {
            throw new NotFoundHttpException(sprintf('Quote not found: %d', $quoteId));
        }

        if ($quote->getBaseSigningId()) {
            throw new BadRequestHttpException(
                sprintf('Quote %d can not be duplicated, since it was transmitted from another node.', $quoteId)
            );
        }

        $quote = clone $quote;
        $quote = $this->fillAttributes($quote, $attributes);
        $this->manager->persist($quote);
        $this->manager->flush();

        return $quote;
    }

    protected function getQuotesRepository(): QuoteRepository
    {
        return $this->manager->getRepository(Quote::class);
    }

    protected function fillAttributes(Quote $quote, array $attributes): mixed
    {
        return $this->denormalizer->denormalize($attributes, Quote::class, null, [
            AbstractNormalizer::OBJECT_TO_POPULATE => $quote,
        ]);
    }
}
