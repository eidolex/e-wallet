<?php

return [
    'enums' => [
        // Transaction Enums
        // 'transaction_status' => Eidolex\EWallet\Enums\TransactionStatus::class,
        'transaction_name' => Eidolex\EWallet\Enums\TransactionName::class,
        'transaction_metadata' => 'array',
    ],

    'transformers' => [
        'top_up_data' => Eidolex\EWallet\Contracts\TopUpDataTransformerContract::class,
        'withdraw_data' => Eidolex\EWallet\Contracts\WithdrawDataTransformerContract::class,
        'transfer_from_data' => Eidolex\EWallet\Transformers\TransferFromDataTransformer::class,
        'transfer_to_data' => Eidolex\EWallet\Transformers\TransferToDataTransformer::class,
    ],

    'models' => [
        'wallet' => Eidolex\EWallet\Models\Wallet::class,
        'transaction' => Eidolex\EWallet\Models\Transaction::class,
        'transfer' => Eidolex\EWallet\Models\Transfer::class,
    ],
];
