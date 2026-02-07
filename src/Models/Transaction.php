<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Models;

use Eidolex\EWallet\Enums\TransactionStatus;
use Eidolex\EWallet\Enums\TransactionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'wallet_id',
        'type',
        'name',
        'amount',
        'status',
        'opening_balance',
        'closing_balance',
        'metadata',
    ];

    /**
     * @return BelongsTo<Wallet,$this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(config('e-wallet.models.wallet'));
    }

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'name' => config('e-wallet.enums.transaction_name'),
            'metadata' => config('e-wallet.enums.transaction_metadata'),
        ];
    }
}
