<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Controller\Signings;

use App\Repository\SigningRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(
    path: '/signings/endcustomer-autocomplete'
)]
class EndCustomerAutocompleteAction extends AbstractController
{
    public function __construct(private SigningRepository $repository)
    {
    }

    public function __invoke(Request $request): Response
    {
        $query = (string)$request->get('query');
        $query = trim($query);
        if (!$query || strlen($query) > 30) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'Query too long. Max length 30 symbols'
            );
        }

        $limit = (int)$request->get('limit');
        $limit = $limit ?: 10;
        $limit = min($limit, 30);

        return $this->json(
            $this->repository->endCustomerAutocomplete($query, $limit)
        );
    }
}
