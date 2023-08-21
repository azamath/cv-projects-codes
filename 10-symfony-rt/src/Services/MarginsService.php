<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Services;

use App\Entity\MarginCache;
use App\Entity\MarginForeign;
use App\Entity\MarginSelf;
use App\Enum\EMarginCalculation;
use App\Enum\EMarginType;
use App\Enum\EMarginValueType;
use App\Repository\MarginCacheRepository;
use App\Repository\MarginSelfRepository;
use App\Services\Margins\MarginLongestMatchAlgorithm;
use App\Services\Margins\NoMatchingMarginException;
use App\Traits\HasLogger;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerAwareInterface;

class MarginsService implements LoggerAwareInterface
{
    use HasLogger;

    protected array $margins = [];

    private float $defaultMarginValue = 0;

    private EMarginValueType $defaultMarginValueType = EMarginValueType::PERCENTAGE;

    private EMarginCalculation $defaultCalculationType = EMarginCalculation::MARGIN;

    public function __construct(
        private MarginSelfRepository $marginSelfRepository,
        private MarginCacheRepository $marginCacheRepository,
        private MarginLongestMatchAlgorithm $marginAlgorithm,
        private ManagerRegistry $doctrine,
        private string $appendix = 'self',
    )
    {
    }

    /**
     * Get entity of {@link MarginForeign}
     *
     * @param int $vendorId
     * @param int $originId
     * @param int $resellerId
     * @param EMarginType $marginType [optional]
     * @param boolean $freeBuffer [optional]
     * @return MarginSelf
     */
    public function getMargin(
        int $vendorId,
        int $originId,
        int $resellerId,
        EMarginType $marginType = EMarginType::PRODUCT,
        bool $freeBuffer = true
    ) {
        /*
         * optimization, check if margin is cached
         */
        $marginHash = $this->getMarginHash(
            $vendorId,
            $originId,
            $resellerId,
            $marginType,
            $this->appendix
        );
        $marginByHash = $this->marginCacheRepository->findOneByHash($marginHash);
        if ($marginByHash) {
            $this->logDebug('Margin cache hit:', [$marginHash]);
            $cachedMargin = new MarginSelf();
            $cachedMargin->setVendorId($vendorId);
            $cachedMargin->setOriginId($originId);
            $cachedMargin->setResellerId($resellerId);
            $cachedMargin->setMarginType($marginType);
            $cachedMargin->setMarginValue($marginByHash->getMarginValue());
            $cachedMargin->setMarginValueType($marginByHash->getMarginValueType());
            $cachedMargin->setCalculationType($marginByHash->getCalculationType());

            return $cachedMargin;
        }

        try {
            /** @var MarginSelf $margin */
            $margin = $this->marginAlgorithm->getMatchingMargins(
                $vendorId,
                $originId,
                $resellerId,
                $this->getBufferedMargins(
                    $marginType,
                    $freeBuffer
                )
            );
        } catch (NoMatchingMarginException $e) {
            // return Fallback Margin
            $this->logDebug('No matched margins, returning fallback margin');
            $marginFallback = new MarginSelf();
            $marginFallback->setMarginType($marginType);
            $marginFallback->setVendorId($vendorId);
            $marginFallback->setOriginId($originId);
            $marginFallback->setResellerId($resellerId);
            $marginFallback->setMarginValue($this->defaultMarginValue);
            $marginFallback->setMarginValueType($this->defaultMarginValueType);
            $marginFallback->setCalculationType($this->defaultCalculationType);

            return $marginFallback;
        }

        // optimization, cache margin
        $marginCache = new MarginCache();
        $marginCache->setMarginHash($marginHash);
        $marginCache->setMarginValue($margin->getMarginValue());
        $marginCache->setMarginValueType($margin->getMarginValueType());
        $marginCache->setCalculationType($margin->getCalculationType());
        $this->doctrine->getManager()->persist($marginCache);
        $this->doctrine->getManager()->flush();

        return $margin;
    }

    /**
     * Get checksum hash.
     * @param int $vendorId
     * @param int $originId
     * @param int $resellerId
     * @param EMarginType $marginType
     * @param string $appendix
     * @return string
     */
    protected function getMarginHash(
        int $vendorId,
        int $originId,
        int $resellerId,
        EMarginType $marginType = EMarginType::PRODUCT,
        string $appendix = ''
    ): string
    {
        $identifier = sprintf(
            '%s;%s;%s;%s;%s',
            $vendorId,
            $originId,
            $resellerId,
            $marginType->value,
            $appendix
        );

        return \md5($identifier);
    }

    /**
     * Buffered the result margins
     * @param EMarginType $marginType
     * @param boolean $freeBuffer
     * @return MarginSelf[]
     */
    protected function getBufferedMargins(EMarginType $marginType, bool $freeBuffer = true): array
    {
        $this->margins = $freeBuffer ? [] : $this->margins;

        if (!isset($this->margins[$marginType->name])) {
            $this->margins[$marginType->name] = $this->marginSelfRepository->findByType($marginType);
        }

        return $this->margins[$marginType->name];
    }

    /**
     * @param float $defaultMarginValue
     * @return MarginsService
     */
    public function setDefaultMarginValue(float $defaultMarginValue): MarginsService
    {
        $this->defaultMarginValue = $defaultMarginValue;
        return $this;
    }

    /**
     * @param EMarginValueType $defaultMarginValueType
     * @return MarginsService
     */
    public function setDefaultMarginValueType(EMarginValueType $defaultMarginValueType): MarginsService
    {
        $this->defaultMarginValueType = $defaultMarginValueType;
        return $this;
    }

    /**
     * @param EMarginCalculation $defaultCalculationType
     * @return MarginsService
     */
    public function setDefaultCalculationType(EMarginCalculation $defaultCalculationType): MarginsService
    {
        $this->defaultCalculationType = $defaultCalculationType;
        return $this;
    }
}
