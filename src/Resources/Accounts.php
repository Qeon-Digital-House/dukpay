<?php

namespace Rrq\Dukpay\Resources;

use InvalidArgumentException;
use Rrq\Dukpay\DukPayResponse;
use Rrq\Dukpay\Support\Api;

/** Authorized accounts and credential-based charges (section 5.1 - 5.7). */
class Accounts extends Resource
{
    /**
     * Create an authorized account.
     *  - V1 (accountCreate): used for Subscriptions.
     *  - V2 (accountCreateV2): returns a credential for merchant-initiated charges (pass purpose=OnInitiate).
     *
     *   businessNo, country, currency, paymentMethod?, authorizationMode? (NONE|PAID_AUTH|UNPAID_AUTH),
     *   amount?, refundable?, callbackUrl?, redirectUrl?, properties{deviceInfo{deviceId, ip}, ...}
     */
    public function create(array $payload, bool $v2 = false): DukPayResponse
    {
        if (! $v2) {
            unset($payload['purpose']);
        }

        return $this->call($v2 ? Api::ACCOUNT_CREATE_V2 : Api::ACCOUNT_CREATE, $this->withRedirectUrl($this->withCallbackUrl($payload)));
    }

    /** Shortcut for create($payload, v2: true). */
    public function createV2(array $payload): DukPayResponse
    {
        return $this->create($payload, true);
    }

    /** Query an authorized account (accountQuery). */
    public function query(?string $accountId = null, ?string $businessNo = null): DukPayResponse
    {
        if ($accountId === null && $businessNo === null) {
            throw new InvalidArgumentException('Provide accountId or businessNo.');
        }

        return $this->call(Api::ACCOUNT_QUERY, $this->filter(['accountId' => $accountId, 'businessNo' => $businessNo]));
    }

    /**
     * Charge using a V2 credential (credentialWithhold).
     *
     *   credential, businessNo, country, currency, orderAmount, callbackUrl?, description?
     */
    public function charge(array $payload): DukPayResponse
    {
        return $this->call(Api::CREDENTIAL_WITHHOLD, $this->withCallbackUrl($payload));
    }

    /** Query a credential-based charge (credentialWithholdQuery). */
    public function chargeQuery(?string $businessNo = null, ?string $orderNo = null): DukPayResponse
    {
        if ($businessNo === null && $orderNo === null) {
            throw new InvalidArgumentException('Provide businessNo or orderNo.');
        }

        return $this->call(Api::CREDENTIAL_WITHHOLD_QUERY, $this->filter(['businessNo' => $businessNo, 'orderNo' => $orderNo]));
    }

    /** Revoke a V2 credential (credentialManagement, action=revoke). */
    public function revokeCredential(string $accountId): DukPayResponse
    {
        return $this->call(Api::CREDENTIAL_MANAGEMENT, ['action' => 'revoke', 'accountId' => $accountId]);
    }
}
