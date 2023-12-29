<?php

namespace App\Factory;

use App\Entity\GoogleUser;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class GoogleUserFactory
{
    /**
     * @var UserRepository
     */
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function create(array $parameters): UserInterface
    {
        $user = new GoogleUser();
        $user
            ->setEmail($parameters['email'])
            ->setFirstName($parameters['given_name'])
            ->setLastName($parameters['family_name'])
            ->setRoles(['ROLE_USER']);
        $this->userRepository->add($user, true);
        return $user;
    }
}