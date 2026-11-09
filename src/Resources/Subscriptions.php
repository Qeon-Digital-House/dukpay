<?php

namespace Rrq\Dukpay\Resources;

use Rrq\Dukpay\DukPayResponse;
use Rrq\Dukpay\Support\Api;

/** Subscription payments (section 5.8). */
class Subscriptions extends Resource
{
    /**
     * Create a subscription on an authorized account (subscriptionCreate).
     *
     *   businessNo, accountId, callbackUrl, amountType (fixed|dynamic), amount?, firstTermAmount?,
     *   interval (specify|day|week|month|quarter|halfYear|year), intervalHours?, totalTerms?,
     *   firstTermStartTime? ("yyyy-MM-dd HH:mm" UTC), gracePolicy? (RESET), redirectUrl?, plans?[{termNo, amount}]
     */
    public function create(array $payload): DukPayResponse
    {
        return $this->call(Api::SUBSCRIPTION_CREATE, $this->withRedirectUrl($this->withCallbackUrl($payload)));
    }

    /** Cancel a subscription; it ends at the end of the current term (subscriptionCancel). */
    public function cancel(string $subscriptionId): DukPayResponse
    {
        return $this->call(Api::SUBSCRIPTION_CANCEL, ['subscriptionId' => $subscriptionId]);
    }

    /** Query a subscription (subscriptionQuery). */
    public function query(string $subscriptionId): DukPayResponse
    {
        return $this->call(Api::SUBSCRIPTION_QUERY, ['subscriptionId' => $subscriptionId]);
    }

    /**
     * Refund a subscription term (subscriptionRefund).
     *
     *   businessNo, subscriptionId, termNo, amount, reason?, callbackUrl?
     */
    public function refund(array $payload): DukPayResponse
    {
        return $this->call(Api::SUBSCRIPTION_REFUND, $this->withCallbackUrl($payload));
    }

    /**
     * Upgrade / downgrade a subscription (switchSubscriptionPlans).
     *
     *   businessNo, sourceSubscriptionId, balanceUsage (FOR_CONVERT|FOR_OFFSET), unitAmountPerHour?,
     *   requireNewSubscription?, newSubscription?{...}
     */
    public function switchPlan(array $payload): DukPayResponse
    {
        return $this->call(Api::SUBSCRIPTION_SWITCH, $payload);
    }
}
