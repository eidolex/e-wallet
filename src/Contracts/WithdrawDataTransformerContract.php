<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Contracts;

use Eidolex\EWallet\Data\WithdrawData;

interface WithdrawDataTransformerContract
{
    public function transform(WithdrawData $data): array;
}
