<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Dto\Collection;

use App\Entity\ExchangeRateInterface;
use Doctrine\Common\Collections\ArrayCollection;

class ExchangeRateCollection extends ArrayCollection
{
    public function __construct(array $elements = [])
    {
        foreach ($elements as $element) {
            if (!$element instanceof ExchangeRateInterface) {
                throw new \BadMethodCallException(sprintf("Element is not instance of %s", ExchangeRateInterface::class));
            }
        }

        parent::__construct($elements);
    }

    /**
     * @inheritDoc
     * @return bool
     */
    public function add($element)
    {
        if (!$element instanceof ExchangeRateInterface) {
            throw new \BadMethodCallException(sprintf("Element is not instance of %s", ExchangeRateInterface::class));
        }
        return parent::add($element);
    }

    /**
     * Get ExchangeRate element for given currency code
     *
     * @param string $currencyCode
     * @return ExchangeRateInterface|null
     */
    public function getByCode(string $currencyCode): ?ExchangeRateInterface
    {
        /** @var ExchangeRateInterface $rate */
        foreach ($this->toArray() as $rate) {
            if ($rate->getCurrencyCode() === $currencyCode) {
                return $rate;
            }
        }

        return null;
    }

    /**
     * Checks whether the collection contains an element with the specified currency code.
     *
     * @param string $currencyCode
     * @return bool
     */
    public function containsCode(string $currencyCode): bool
    {
        return null !== $this->getByCode($currencyCode);
    }
}
