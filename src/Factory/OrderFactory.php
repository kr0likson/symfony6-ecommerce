<?php

namespace App\Factory;

use App\Entity\Address;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class OrderFactory
{
    private $security;
    private $entityManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    public function create($params): Order
    {
        $order = new Order();
        $address = $this->createAddress($params);
        $order
            ->setCustomer($this->security->getUser())
            ->setFirstName($params['inputFirstName'])
            ->setLastName($params['inputLastName'])
            ->setAddress($address);

        if (isset($params['singleProduct'])) {
            $product = $this->entityManager->getRepository(Product::class)->find($params['singleProduct']);
            $this->addOrderItem($order, $product, 1);
        } else {
            $cartItems = $this->security->getUser()->getCart()->getCartItems();
            foreach ($cartItems as $cartItem) {
                $this->addOrderItem($order, $cartItem->getProduct(), $cartItem->getQuantity());
            }
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();
        return $order;
    }

    private function createAddress($params): Address
    {
        $address = new Address();
        $address
            ->setAddress($params['inputAddress'])
            ->setAddress2($params['inputAddress2'])
            ->setCity($params['inputCity'])
            ->setCountry($params['inputCountry'])
            ->setState($params['inputState'])
            ->setZipCode($params['inputZip']);

        return $address;
    }

    private function addOrderItem(Order $order, Product $product, int $quantity): void
    {
        $orderItem = new OrderItem();
        $orderItem->setProduct($product);
        $orderItem->setQuantity($quantity);
        $order->addOrderItem($orderItem);
        $this->entityManager->persist($orderItem);
    }
}