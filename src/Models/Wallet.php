<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Wallet extends Model
{
    use HasUuids;

    protected $fillable = [
        'owner_type',
        'owner_id',
        'balance',
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
