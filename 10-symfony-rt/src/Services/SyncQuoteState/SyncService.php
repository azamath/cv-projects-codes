<?php


namespace App\Services\SyncQuoteState;

use App\Entity\Quote;
use App\Repository\SystemRepository;
use App\Services\SignedHttpRequestService;
use App\Traits\HasLogger;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerAwareInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class SyncService implements LoggerAwareInterface
{
    use HasLogger;

    public function __construct(
        private ManagerRegistry $doctrine,
        private SystemRepository $systemRepository,
        private SignedHttpRequestService $httpRequestService,
        private string $app2routePrefix,
    )
    {
    }

    public function sync(Quote $quote): bool
    {
        $remoteSigningId = $quote->getBaseSigningId();
        $targetSystem = $this->systemRepository->findOneByCompanyId($quote->getOriginCompanyId());
        $url = $targetSystem->getHost() . ":" . $targetSystem->getPort()
            . "{$this->app2routePrefix}/internal/signings/{$remoteSigningId}/state";

        $synced = false;
        try {
            $this->httpRequestService->fetch('PUT', $url, ['state' => $quote->getResolvedState()->value]);
            $synced = true;
            $quote->setStateSyncDate(new \DateTime());
            $quote->setStateSynced(true);
            $this->logNotice("{$quote}: successfully synced state: {$quote->getResolvedState()->name}");
        } catch (ExceptionInterface $e) {
            $quote->setStateSynced(false);
            $this->logWarning("{$quote}: failed syncing state. Exception: " . $e->getMessage());
        }

        $quote->setStateSyncTryDate(new \DateTime());
        $quote->incrementStateSyncTries();

        $this->doctrine->getManager()->flush();

        return $synced;
    }
}
