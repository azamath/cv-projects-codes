<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Dto\ImportQuote;

use App\Entity\Company;
use App\Entity\ImportLogQuote;
use App\Entity\User;

class Context
{
    /**
     * @var ImportLogQuote Import Quote Log entity created for this import event
     */
    private ImportLogQuote $importLogQuote;

    /**
     * @var mixed List of clean data for quote
     */
    private mixed $data;

    /**
     * @var mixed Raw data parsed from quote file
     */
    private mixed $rawData;

    /**
     * @var Company Vendor company
     */
    private Company $vendor;

    /**
     * @var Company Reseller company
     */
    private Company $reseller;

    /**
     * @var User Created user
     */
    private User $user;

    /**
     * @return ImportLogQuote
     */
    public function getImportLogQuote(): ImportLogQuote
    {
        return $this->importLogQuote;
    }

    /**
     * @param ImportLogQuote $importLogQuote
     * @return Context
     */
    public function setImportLogQuote(ImportLogQuote $importLogQuote): Context
    {
        $this->importLogQuote = $importLogQuote;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRawData(): mixed
    {
        return $this->rawData;
    }

    /**
     * @param mixed $rawData
     * @return Context
     */
    public function setRawData(mixed $rawData): Context
    {
        $this->rawData = $rawData;
        return $this;
    }

    /**
     * @return Company
     */
    public function getVendor(): Company
    {
        return $this->vendor;
    }

    /**
     * @param Company $vendor
     * @return Context
     */
    public function setVendor(Company $vendor): Context
    {
        $this->vendor = $vendor;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     * @return Context
     */
    public function setData(mixed $data): Context
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return Company
     */
    public function getReseller(): Company
    {
        return $this->reseller;
    }

    /**
     * @param Company $reseller
     * @return Context
     */
    public function setReseller(Company $reseller): static
    {
        $this->reseller = $reseller;
        return $this;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return Context
     */
    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
