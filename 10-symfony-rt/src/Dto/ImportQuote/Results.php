<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Dto\ImportQuote;

use Doctrine\Common\Collections\ArrayCollection;

class Results extends ArrayCollection
{
    private array $errors = [];

    public function addError(string $message)
    {
        $this->errors[] = $message;
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        $errors = $this->errors;
        foreach ($this->toArray() as $result) {
            $errors = array_merge($errors, $result->getValidationErrors());
        }

        return $errors;
    }

    /**
     * Has any errors
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return count($this->getErrors()) > 0;
    }
}
