<?php

namespace Rrq\Dukpay\Resources;

use Rrq\Dukpay\DukPayResponse;
use Rrq\Dukpay\Support\Api;

/** Accounts & reconciliation (section 8). */
class Finance extends Resource
{
    /** Query merchant account balances, optionally for one currency (accountBalance). */
    public function balance(?string $currency = null): DukPayResponse
    {
        return $this->call(Api::ACCOUNT_BALANCE, $this->filter(['currency' => $currency]));
    }

    /** Currency conversion estimate for pricing mode (currencyExchange). */
    public function currencyExchange(string $sourceCurrency, string $targetCurrency, int|float|string|null $sourceAmount = null, ?string $transactionType = null): DukPayResponse
    {
        return $this->call(Api::CURRENCY_EXCHANGE, $this->filter([
            'sourceCurrency' => $sourceCurrency,
            'targetCurrency' => $targetCurrency,
            'sourceAmount' => $sourceAmount === null ? null : (string) $sourceAmount,
            'transactionType' => $transactionType,
        ]));
    }

    /**
     * Export a reconciliation file (financial). Returns data.signUrl to download.
     *
     *   date (yyyy-MM-dd), reconciliationType (TRADE|BALANCE), transactionType?, country?,
     *   reconciliationPeriod? (DAY|MONTH), accountNo?, currency?, fileType? (csv|xlsx)
     */
    public function reconciliation(array $payload): DukPayResponse
    {
        return $this->call(Api::FINANCIAL, $payload);
    }

    /** Query multiparty revenue-sharing details for an order (profitSharingQuery). */
    public function profitSharing(string $orderNo): DukPayResponse
    {
        return $this->call(Api::PROFIT_SHARING_QUERY, ['orderNo' => $orderNo]);
    }
}
