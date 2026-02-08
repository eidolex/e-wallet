<?php

declare(strict_types=1);

namespace Eidolex\EWallet\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transfer extends Model
{
    use HasUuids;

    protected $fillable = [
        'from_transaction_id',
        'to_transaction_id',
        'amount',
    ];

    /**
     * @return BelongsTo<Transaction,$this>
     */
    public function from(): BelongsTo
    {
        /**
         * @var class-string<\Eidolex\EWallet\Models\Transaction> $class
         */
        $class = config('e-wallet.models.transaction');

        return $this->belongsTo(
            $class,
            'from_transaction_id'
        );
    }

    /**
     * @return BelongsTo<Transaction,$this>
     */
    public function to(): BelongsTo
    {
        /**
         * @var class-string<\Eidolex\EWallet\Models\Transaction> $class
         */
        $class = config('e-wallet.models.transaction');

        return $this->belongsTo($class, 'to_transaction_id');
    }

    protected function casts(): array
    {
        return [];
    }
}
