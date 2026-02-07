<?php

return [
    'enums' => [
        // Transaction Enums
        // 'transaction_status' => Eidolex\EWallet\Enums\TransactionStatus::class,
        // 'transaction_name' => Eidolex\EWallet\Enums\TransactionName::class,
        'transaction_metadata' => 'array',
    ],

    'transformers' => [
        'transfer_from_data' => Eidolex\EWallet\Contracts\TransferDataTransformerContract::class,
        'transfer_to_data' => Eidolex\EWallet\Contracts\TransferDataTransformerContract::class,
        'withdraw_data' => Eidolex\EWallet\Contracts\WithdrawDataTransformerContract::class,
        'top_up_data' => Eidolex\EWallet\Contracts\TopUpDataTransformerContract::class,
    ],
];
