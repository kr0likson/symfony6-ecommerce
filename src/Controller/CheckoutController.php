<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Factory\OrderFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

class CheckoutController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    private UserInterface $user;

    private OrderFactory $orderFactory;

    public function __construct(EntityManagerInterface $entityManager, Security $security, OrderFactory $orderFactory)
    {
        $this->entityManager = $entityManager;
        $this->user = $security->getUser();
        $this->orderFactory = $orderFactory;
    }
    #[Route('/checkout/{productId?}', name: 'app_checkout')]
    public function index(?int $productId = null): Response
    {
        $productRepository = $this->entityManager->getRepository(Product::class);

        /**
         * @var User $user
         */
        $user = $this->user;
        return $this->render('checkout/index.html.twig', [
            'singleProduct' => $productId ? $productRepository->find($productId) : null,
            'cartProducts' => $productId ? [] : $user->getCart()?->getCartItems()
        ]);
    }

    #[Route('/checkout_process', name: 'app_checkout_process', methods: ['POST'])]
    public function processCheckout(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->validateCsrfToken($request);
        $params = $request->request->all();
        $order = $this->orderFactory->create($params);
        return $this->redirectToRoute('app_order_details', ['orderId' => $order->getId()]);
    }

    private function validateCsrfToken(Request $request): void
    {
        $token = $request->request->get('token');
        if (!$this->isCsrfTokenValid('checkout', $token)) {
            throw new AccessDeniedException('Invalid CSRF Token');
        }
    }

}
