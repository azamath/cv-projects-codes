<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Services;

use App\Repository\InfoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;

class SystemInfoService
{

    public const BASE_CURRENCY_CODE = 'system.base.currency.code';
    public const SIGNING_INACTIVATE_EXPIRATION_DAYS = 'signing.inactivateExpirationDays';

    private ArrayCollection $infos;

    public function __construct(private InfoRepository $infoRepository, private EntityManagerInterface $manager)
    {
    }

    /**
     * Get specific system info by key
     *
     * @param string $key
     * @return string|null
     */
    public function get(string $key): ?string
    {
        if (!isset($this->infos)) {
            $this->infos = new ArrayCollection();
            $infos = $this->infoRepository->findAll();
            foreach ($infos as $info) {
                $this->infos->set($info->getKey(), $info);
            }
        }

        return $this->infos->get($key)?->getValue();
    }

    public function set(string $key, ?string $value): void
    {
        $currValue = $this->get($key);
        if ($currValue !== $value) {
            $info = $this->infos->get($key) ?? new \App\Entity\Info();
            $info->setKey($key);
            $info->setValue($value);
            $this->infos->set($key, $info);

            if (null === $info->getInfoId()) {
                $this->manager->persist($info);
            }
            $this->manager->flush();
        }
    }

    /**
     * Get system currency code
     *
     * @return string|null
     */
    public function getCurrencyCode(): ?string
    {
        return $this->get(self::BASE_CURRENCY_CODE);
    }
}
