<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Transformers;

use Eidolex\EWallet\Contracts\TopUpDataTransformerContract;
use Eidolex\EWallet\Data\TopUpData;

class TopUpDataTransformer implements TopUpDataTransformerContract
{
    public function transform(TopUpData $data): array
    {
        return [
            'name' => $data->name,
            'amount' => $data->amount,
            'status' => $data->status,
            'metadata' => $data->metadata,
        ];
    }
}
