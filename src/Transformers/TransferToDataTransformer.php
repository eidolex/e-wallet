<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Transformers;

use Eidolex\EWallet\Contracts\TransferDataTransformerContract;
use Eidolex\EWallet\Data\TransferData;
use Eidolex\EWallet\Enums\TransactionStatus;

class TransferToDataTransformer implements TransferDataTransformerContract
{
    public function transform(TransferData $data): array
    {
        return [
            'name' => $data->name,
            'status' => TransactionStatus::Completed,
            'amount' => $data->amount,
            'metadata' => $data->toMetadata,
        ];
    }
}
