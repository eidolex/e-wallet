<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Contracts;

use Eidolex\EWallet\Data\TopUpData;

interface TopUpDataTransformerContract
{
    public function transform(TopUpData $data): array;
}
