<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Handler;

use App\Enum\EAppContext;
use App\Enum\ESigningState;
use App\Repository\SigningRepository;
use App\Repository\SigningStateRepository;
use App\Services\SystemInfoService;
use Doctrine\Common\Collections\ArrayCollection;

class ProcessExpiredSigningsHandler
{
    public function __construct(
        private string $appContext,
        private SystemInfoService $systemInfoService,
        private SigningRepository $signingRepository,
        private SigningStateRepository $signingStateRepository,
    )
    {
    }

    /**
     * @return int|null Number of processed signings. Null if not applicable.
     */
    public function handle(): ?int
    {
        if ($this->appContext != EAppContext::RESELLER->value) {
            return null;
        }

        $days = $this->systemInfoService->get(SystemInfoService::SIGNING_INACTIVATE_EXPIRATION_DAYS);
        if (!is_numeric($days)) {
            return null;
        }

        $processed = 0;
        $iterations = 0;
        while (true) {
            $iterations++;
            // prevent infinite loop
            if ($iterations > 100) {
                break;
            }

            $expiredSignings = $this->signingRepository->findExpired((int)$days, ESigningState::PENDING);
            $expiredSignings = new ArrayCollection($expiredSignings);
            if ($expiredSignings->isEmpty()) {
                break;
            }

            $processed += $this->updateSigningStates($expiredSignings);
        }

        return $processed;
    }

    protected function updateSigningStates(ArrayCollection $expiredSignings): int
    {
        $expiredSigningsIds = $expiredSignings->map(fn($signing) => $signing->getSigningId());
        return $this->signingStateRepository->updateForSignings($expiredSigningsIds->toArray(), [
            'state' => ESigningState::INACTIVATED->value,
            'modifiedDate' => date('c'),
            'stateUpdateMethod' => 'schedule',
        ]);
    }
}
