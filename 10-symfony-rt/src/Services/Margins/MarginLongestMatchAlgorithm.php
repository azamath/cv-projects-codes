<?php

namespace App\Services\Margins;

use App\Entity\MarginInterface;
use InvalidArgumentException;
use RuntimeException;
use function uasort;

class MarginLongestMatchAlgorithm
{
    /**
     * @param int $vendorId
     * @param int $originId
     * @param int $resellerId
     * @param MarginInterface[] $unfilteredMargins
     * @return MarginInterface
     * @throws NoMatchingMarginException
     */
    public function getMatchingMargins(
        int $vendorId,
        int $originId,
        int $resellerId,
        array $unfilteredMargins
    ): MarginInterface
    {
        return $this->filterMarginsByMostSpecificTuple(
            $this->filterMarginsByMostMinimalWildcardCount(
                $this->filterMargins(
                    $unfilteredMargins,
                    $vendorId,
                    $originId,
                    $resellerId
                )
            )
        );
    }


    /**
     * Filter a {@link \App\Entity\MarginInterface} list by tuple
     * parameters. Validates parameters and throws Exception if a parameter
     * is invalid.
     *
     * @throws InvalidArgumentException
     * @param MarginInterface[] $unfilteredMargins
     * @param int $vendorId
     * @param int $originId
     * @param int $resellerId
     * @return MarginInterface[] $filteredMargins
     */
    public function filterMargins(
        array $unfilteredMargins,
        int $vendorId,
        int $originId,
        int $resellerId
    ): array
    {
        // throws \InvalidArgumentException if parameters are invalid
        $this->validate(
            $vendorId,
            $originId,
            $resellerId
        );


        $filteredMargins = array();

        foreach ($unfilteredMargins as $margin) {
            /* @var $margin MarginInterface */
            if ((0 === $vendorId || null === $margin->getVendorId() || $margin->getVendorId() === $vendorId) &&
                (0 === $originId || null === $margin->getOriginId() || $margin->getOriginId() === $originId) &&
                (0 === $resellerId || null === $margin->getResellerId() || $margin->getResellerId() === $resellerId)
            ) {
                $filteredMargins[] = $margin;
            }
        }

        return $filteredMargins;
    }


    /**
     * Filter list of {@link \App\Entity\MarginInterface} by least wildcard count
     *
     * @param MarginInterface[] $margins
     * @return MarginInterface[]
     */
    public function filterMarginsByMostMinimalWildcardCount(array $margins): array
    {
        // return empty array if input is empty
        if (empty($margins)) {
            return array();
        }

        /*
         * Build up list sorted by amount of wildcards
         *
         * array (
         *      {{WILDCARD-COUNT}} => array (
         *          {{Margin}},
         *          {{Margin}},
         *          ..
         *      )
         * )
         */
        $filteredMargins = array();

        foreach ($margins as $margin) {
            $wildcards = $this->countWildcardsInMargin($margin);
            $filteredMargins[$wildcards][] = $margin;
        }

        // sort the list by the wildcars-amount index
        ksort($filteredMargins);

        // returns list of margins with the least amount of wildcards
        return array_shift($filteredMargins);
    }


    /**
     * Filter list of {@link \App\Entity\MarginInterface} by most specific tuple
     * <br/>
     * Priority list as follows with the highest priority first:
     * <ol>
     *  <li>reseller</li>
     *  <li>vendor</li>
     * </ol>
     *
     * @param MarginInterface[] $margins
     * @return MarginInterface
     * @throws RuntimeException If multiple margin matches occur
     * @throws NoMatchingMarginException
     */
    public function filterMarginsByMostSpecificTuple(array $margins): MarginInterface
    {
        if (empty($margins)) {
            throw new NoMatchingMarginException(
                'Empty margin rule match.'
            );
        }

        uasort(
            $margins,
            array($this, 'sortMarginsByMostSpecificTuple')
        );

        return array_shift($margins);
    }


    /**
     * Count wildcard property values in {@link \App\Entity\MarginInterface}
     * @param MarginInterface $margin
     * @return int
     */
    public function countWildcardsInMargin(MarginInterface $margin): int
    {
        $wildcards = 0;

        $wildcards += 1 > $margin->getVendorId() ? 1 : 0;
        $wildcards += 1 > $margin->getOriginId() ? 1 : 0;
        $wildcards += 1 > $margin->getResellerId() ? 1 : 0;

        return $wildcards;
    }


    /**
     * Sort margins list by checking if either the left or the right operator is wildcard
     * and return success for the other. In case of equality, step further to the next
     * prioritized parameter.
     *
     * @param MarginInterface $a
     * @param MarginInterface $b
     * @return int
     * @throws RuntimeException
     */
    public function sortMarginsByMostSpecificTuple(
        MarginInterface $a,
        MarginInterface $b
    ): int
    {

        if (null !== $a->getResellerId() && null === $b->getResellerId()) {
            return -1;
        } elseif (null === $a->getResellerId() && null !== $b->getResellerId()) {
            return 1;
        }

        if (null !== $a->getOriginId() && null === $b->getOriginId()) {
            return -1;
        } elseif (null === $a->getOriginId() && null !== $b->getOriginId()) {
            return 1;
        }

        if (null !== $a->getVendorId() && null === $b->getVendorId()) {
            return -1;
        } elseif (null === $a->getVendorId() && null !== $b->getVendorId()) {
            return 1;
        }

        // $a and $b are totally equal, should never happen
        throw new RuntimeException('Multiple margin matches');
    }

    /**
     * Validates parameter. Throws exception if a parameter is invalid.
     *
     * @throws InvalidArgumentException
     * @param int $vendorId
     * @param int $originId
     * @param int $resellerId
     */
    protected function validate(
        int $vendorId,
        int $originId,
        int $resellerId
    ) {
        // vendor id
        if (!(0 <= $vendorId)) {
            throw new InvalidArgumentException('VendorId must be greater then or equal zero');
        }

        // origin id
        if (!(0 <= $originId)) {
            throw new InvalidArgumentException('OriginId must be greater then or equal zero');
        }

        // reseller id
        if (!(0 <= $resellerId)) {
            throw new InvalidArgumentException('ResellerId must be greater then or equal zero');
        }
    }
}
