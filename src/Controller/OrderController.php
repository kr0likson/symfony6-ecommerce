<?php

namespace App\Controller;

use App\Entity\Order;
use App\Factory\OrderFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

class OrderController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ?UserInterface $user;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->user = $security->getUser();
    }

    #[Route('/order_details/{orderId?}', name: 'app_order_details')]
    public function getOrderDetails(int $orderId): Response
    {
        $order = $this->entityManager->getRepository(Order::class)->find($orderId);

        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        if ($this->user !== $order->getCustomer()) {
            throw new AccessDeniedException('You do not have access to this payment page.');
        }
        return $this->render('order/order.html.twig', [
            'order' => $order
        ]);
    }
}
