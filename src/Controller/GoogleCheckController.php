<?php

namespace App\Controller;

use App\Entity\User;
use App\Factory\GoogleUserFactory;
use App\Repository\UserRepository;
use App\Security\LoginFormAuthenticator;
use Doctrine\Persistence\ManagerRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class GoogleCheckController extends AbstractController
{
    /**
     * @var ManagerRegistry
     */
    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }
    #[Route('/google/check', name: 'app_check_google')]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('google') // key used in knpu_oauth2_client.yaml
            ->redirect(['email'], []);
    }

    #[Route('/google/connect', name: 'app_connect_google')]
    public function connectService(
        Request $request,
        ClientRegistry $clientRegistry,
        GoogleUserFactory $userFactory,
        UserAuthenticatorInterface $userAuthenticator,
        LoginFormAuthenticator $authenticator,
    ): RedirectResponse {
        $client = $clientRegistry->getClient('google');
        $accessToken = $client->getAccessToken();
        $userCredentials = $client->fetchUserFromToken($accessToken)->toArray();
        /**
         * @var UserRepository $userRepository
         */
        $userRepository = $this->managerRegistry->getRepository(User::class);
        $userCredentials['token'] = $accessToken->getToken();
        $user = $userRepository->findOneByEmailField($userCredentials['email']);
        if (!$user) {
            $user = $userFactory->create($userCredentials);
        }
        $userAuthenticator->authenticateUser(
            $user,
            $authenticator,
            $request
        );
        return new RedirectResponse($this->generateUrl('app_index'));
    }
}
