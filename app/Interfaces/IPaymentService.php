<?php

namespace App\Interfaces;

interface IPaymentService
{
    public function listPlans(int $planId = null);

    public function deletePlan(int $planId);

    public function createPlan(array $plan);

    public function updatePlan(array $plan);

    public function createSubscription(array $subscriptionData);

    public function getSubscription(string $subscriptionId);

    public function listSubscriptions();

    public function updateSubscription(string $subscriptionId, array $data);

    public function cancelSubscription(string $subscriptionId);

    public function setPaymentMethod(string $subscriptionId, array $paymentData);

}
