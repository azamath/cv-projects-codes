<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Handler\ImportQuote;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class DataDenormalizer
{
    public function __construct(private DenormalizerInterface $denormalizer)
    {
    }

    /**
     * @param mixed $data
     * @return \App\Dto\ImportQuote\Quote[]
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function denormalize(mixed $data): array
    {
        $context = [];
        return $this->denormalizer->denormalize($data, \App\Dto\ImportQuote\Quote::class . '[]', null, $context);
    }
}
