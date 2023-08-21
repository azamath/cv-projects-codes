<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Handler\ImportQuote;

use App\Dto\ImportQuote\Result;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntitiesValidator
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    /**
     * @param Result $result
     * @param string $prefix
     * @return string[]
     */
    public function validate(Result $result, string $prefix): array
    {
        $allErrors = array_merge(
            $this->validateEntity($prefix . '.quote', $result->getQuote()),
            $this->validateEntity($prefix . '.endCustomer', $result->getEndCustomer()),
            $this->validateEntity($prefix . '.quoteCompany', $result->getQuoteCompany()),
        );
        foreach ($result->getProducts() as $i => $quoteProduct) {
            $allErrors = array_merge(
                $allErrors,
                $this->validateEntity("$prefix.product[$i]", $quoteProduct),
            );
        }
        foreach ($result->getExchangeRates() as $i => $exchangeRate) {
            $allErrors = array_merge(
                $allErrors,
                $this->validateEntity("$prefix.exchangeRate[$i]", $exchangeRate),
            );
        }

        return $allErrors;
    }

    protected function validateEntity(string $prefix, $entity): array
    {
        $result = [];

        /** @var ConstraintViolationInterface $error */
        foreach ($this->validator->validate($entity) as $error) {
            $result[] = sprintf(
                "%s.%s: %s %s",
                $prefix,
                $error->getPropertyPath(),
                $error->getMessage(),
                json_encode($error->getInvalidValue())
            );
        }

        return $result;
    }
}
