<?php

return [
    'enums' => [
        // Transaction Enums
        // 'transaction_status' => Eidolex\EWallet\Enums\TransactionStatus::class,
        'transaction_name' => Eidolex\EWallet\Enums\TransactionName::class,
        'transaction_metadata' => 'array',
    ],

    'models' => [
        'wallet' => Eidolex\EWallet\Models\Wallet::class,
        'transaction' => Eidolex\EWallet\Models\Transaction::class,
        'transfer' => Eidolex\EWallet\Models\Transfer::class,
    ],
];
