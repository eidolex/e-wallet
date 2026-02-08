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

    protected $casts = [
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
    ];

    /**
     * @return BelongsTo<Wallet,$this>
     */
    public function wallet(): BelongsTo
    {
        /**
         * @var class-string<\Eidolex\EWallet\Models\Wallet> $class
         */
        $class = config('e-wallet.models.wallet');

        return $this->belongsTo($class);
    }

    protected function casts(): array
    {
        $casts = array_merge($this->casts, [
            'name' => config('e-wallet.enums.transaction_name'),
            'metadata' => config('e-wallet.enums.transaction_metadata'),
        ]);

        return $casts;
    }
}
