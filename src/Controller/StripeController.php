<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Stripe;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

class StripeController extends AbstractController
{
    private UserInterface $user;
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->user = $security->getUser();
    }

    #[Route('/stripe{orderId}', name: 'app_stripe')]
    public function index(int $orderId): Response
    {
        $order = $this->entityManager->getRepository(Order::class)->find($orderId);
        if ($this->user !== $order->getCustomer()) {
            throw new AccessDeniedException('You do not have access to this payment page.');
        }
        $items = [];
        foreach ($order->getOrderItem() as $orderItem) {
            $items[] = [
                "quantity" => $orderItem->getQuantity(),
                "price_data" => [
                    "currency" => "usd",
                    "unit_amount" => $orderItem->getProduct()->getPrice() * 100,
                    "product_data" => [
                        "name" => $orderItem->getProduct()->getName(),
                    ]
                ]
            ];
        }
        Stripe\Stripe::setApiKey($_ENV["STRIPE_SECRET"]);
        $checkoutSession = Stripe\Checkout\Session::create([
            "mode" => "payment",
            "success_url" => 'http://localhost/e-commerce/public/order_details/'.$order->getId(),
            "line_items" => [
                $items
            ]
        ]);
        $order->setPaymentStatus('IN_PROGRESS');
        $this->entityManager->flush();
        return $this->redirect($checkoutSession->url);
    }

    #[Route('/stripe/webhook/success', name: 'app_stripe_webhook_success', methods: ['POST'])]
    public function webhookSuccess(Request $request): Response
    {
        $stripeData = json_decode($request->getContent(), true);
        return new Response('Webhook Success', Response::HTTP_OK);
    }
}