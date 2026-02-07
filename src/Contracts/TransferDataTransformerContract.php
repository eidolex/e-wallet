<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Contracts;

use Eidolex\EWallet\Data\TransferData;

interface TransferDataTransformerContract
{
    public function transform(TransferData $data): array;
}
