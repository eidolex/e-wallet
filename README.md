# Eidolex E-Wallet

A Laravel package for managing wallets, transfers, and transactions. Attach a wallet to any Eloquent model with a simple trait and start processing top-ups, withdrawals, and peer-to-peer transfers.

## Requirements

- PHP ^8.2
- Laravel ^12.0
- [spatie/laravel-data](https://github.com/spatie/laravel-data) ^4.19

## Installation

Add the package to your `composer.json` repositories (local path or VCS), then require it:

```bash
composer require eidolex/e-wallet
```

The service provider is auto-discovered via `composer.json` extra config.

Run the migrations:

```bash
php artisan migrate
```

Optionally publish the config and migrations:

```bash
# Config only
php artisan vendor:publish --tag=e-wallet-config

# Migrations only
php artisan vendor:publish --tag=e-wallet-migrations
```

## Setup

Add the `HasWallet` trait and `HasWalletContract` interface to any Eloquent model:

```php
use Eidolex\EWallet\Concerns\HasWallet;
use Eidolex\EWallet\Contracts\HasWalletContract;

class User extends Model implements HasWalletContract
{
    /** @use HasWallet<\Eidolex\EWallet\Enums\TransactionName, \Eidolex\EWallet\Models\Wallet, \Eidolex\EWallet\Models\Transaction, \Eidolex\EWallet\Models\Transfer> */
    use HasWallet;
}
```

This gives the model access to `wallet()`, `transactions()`, `topUp()`, `withdraw()`, and `transfer()`. To use your own Wallet, Transaction, or Transfer models, see [Custom models](#custom-models).

## Usage

### Top Up

Add funds to a wallet. The wallet is automatically created on first use.

```php
use Eidolex\EWallet\Data\TopUpData;
use Eidolex\EWallet\Enums\TransactionName;
use Eidolex\EWallet\Enums\TransactionStatus;

$transaction = $user->topUp(new TopUpData(
    name: TransactionName::TopUp,
    amount: 10000,
    status: TransactionStatus::Completed, // default
    metadata: ['source' => 'credit_card'],
));

$transaction->id;     // UUID
$transaction->amount; // 10000
$transaction->type;   // TransactionType::Deposit
```

You can pass a custom DTO that extends `TopUpData` (e.g. to add fees, computed metadata, or opening/closing balance). See [Custom TopUpData](#custom-topupdata) for an example.

### Withdraw

Deduct funds from a wallet.

```php
use Eidolex\EWallet\Data\WithdrawData;
use Eidolex\EWallet\Enums\TransactionName;

$transaction = $user->withdraw(new WithdrawData(
    name: TransactionName::Withdraw,
    amount: 3000,
    metadata: ['reason' => 'cash_out'],
));
```

You can pass a custom DTO that extends `WithdrawData` and overrides `fields(Wallet $wallet)`. See [Custom TopUpData](#custom-topupdata) for the same pattern.

### Transfer

Move funds between two wallet holders. Creates a withdrawal transaction for the sender and a deposit transaction for the receiver, linked by a `Transfer` record.

```php
use Eidolex\EWallet\Data\TransferData;
use Eidolex\EWallet\Enums\TransactionName;

$transfer = $sender->transfer(new TransferData(
    to: $receiver,
    name: TransactionName::Gift,
    amount: 5000,
    fromMetadata: ['note' => 'Birthday gift'],
    toMetadata: ['from' => $sender->name],
));

$transfer->from; // Transaction (sender, type: Withdraw)
$transfer->to;   // Transaction (receiver, type: Deposit)
$transfer->amount;
```

You can pass a custom DTO that extends `TransferData` and overrides `fromFields(Wallet $wallet)` and `toFields(Wallet $wallet)`. See [Custom TopUpData](#custom-topupdata) for the same pattern.

### Accessing the Wallet & Transactions

```php
// Wallet (auto-created on first operation)
$wallet = $user->wallet;
$wallet->balance; // current balance

// All transactions through the wallet
$transactions = $user->transactions;
```

### Transaction Statuses

Balance is **only updated** when a transaction's status is `Completed`. Use `Pending` to record a transaction without affecting the balance.

```php
use Eidolex\EWallet\Enums\TransactionStatus;

// Pending transaction — balance unchanged
$transaction = $user->topUp(new TopUpData(
    name: TransactionName::TopUp,
    amount: 5000,
    status: TransactionStatus::Pending,
));
```

## Data Transfer Objects

All operations use [Spatie Laravel Data](https://spatie.be/docs/laravel-data) DTOs. The `name` parameter accepts any `UnitEnum`, so you can use the built-in `TransactionName` or your own enum. Transaction attributes are built from each DTO: `TopUpData` and `WithdrawData` expose `fields(Wallet $wallet)`, and `TransferData` exposes `fromFields(Wallet $wallet)` and `toFields(Wallet $wallet)`. Extend the DTO classes and override these methods if you need custom transaction data (e.g. computed metadata or balances).

| DTO | Parameters |
|---|---|
| `TopUpData` | `name` (UnitEnum), `amount` (int), `status` (TransactionStatus = Completed), `metadata` (?array) |
| `WithdrawData` | `name` (UnitEnum), `amount` (int), `status` (TransactionStatus = Completed), `metadata` (?array) |
| `TransferData` | `to` (HasWalletContract), `name` (UnitEnum), `amount` (int), `fromMetadata` (?array), `toMetadata` (?array) |

## Customization

### Custom Transaction Name Enum

By default, transaction names use `Eidolex\EWallet\Enums\TransactionName` which provides: `TopUp`, `Withdraw`, `Gift`, and `Purchase`.

To use your own enum, create it and update the config:

```php
// app/Enums/MyTransactionName.php
enum MyTransactionName: string
{
    case TopUp = 'top_up';
    case Withdraw = 'withdraw';
    case Subscription = 'subscription';
    case Refund = 'refund';
}
```

```php
// config/e-wallet.php
'enums' => [
    'transaction_name' => App\Enums\MyTransactionName::class,
    // ...
],
```

Then pass your enum when creating transactions:

```php
$user->topUp(new TopUpData(
    name: MyTransactionName::Subscription,
    amount: 2999,
));
```

### Custom Transaction Status Enum

Similarly, you can replace the default `TransactionStatus` enum:

```php
// config/e-wallet.php
'enums' => [
    'transaction_status' => App\Enums\MyTransactionStatus::class,
    // ...
],
```

### Custom Metadata Cast

The `metadata` column is cast to `array` by default. You can change this in the config:

```php
// config/e-wallet.php
'enums' => [
    'transaction_metadata' => 'collection', // or AsArrayObject::class, etc.
],
```

### Custom TopUpData

Extend `TopUpData` and override `fields(Wallet $wallet)` when you need custom transaction attributes (e.g. computed metadata, fees, or opening/closing balance).

```php
// app/Data/CustomTopUpData.php
namespace App\Data;

use Eidolex\EWallet\Data\TopUpData;
use Eidolex\EWallet\Models\Wallet;

/**
 * Custom top-up DTO that adds a 2% fee, processed_at timestamp, and opening/closing balance to transactions.
 *
 * @extends TopUpData<\Eidolex\EWallet\Enums\TransactionName,\Eidolex\EWallet\Models\Wallet>
 */
class CustomTopUpData extends TopUpData
{
    public function fields(Wallet $wallet): array
    {
        $fee = (int) ($this->amount * 0.02);
        $metadata = array_merge($this->metadata ?? [], [
            'processed_at' => now()->toISOString(),
            'fee' => $fee,
        ]);

        return [
            'name' => $this->name,
            'amount' => $this->amount,
            'status' => $this->status,
            'metadata' => $metadata,
            'opening_balance' => $wallet->balance,
            'closing_balance' => $wallet->balance + $this->amount,
        ];
    }
}
```

Usage:

```php
use App\Data\CustomTopUpData;
use Eidolex\EWallet\Enums\TransactionName;

$transaction = $user->topUp(new CustomTopUpData(
    name: TransactionName::TopUp,
    amount: 10000,
    metadata: ['source' => 'credit_card'],
));
```

The same pattern applies to `WithdrawData` (override `fields(Wallet $wallet)`) and `TransferData` (override `fromFields(Wallet $wallet)` and `toFields(Wallet $wallet)`).

## Configuration Reference

```php
// config/e-wallet.php
return [
    'enums' => [
        // Cast for the Transaction `name` column (string or enum class)
        'transaction_name' => Eidolex\EWallet\Enums\TransactionName::class,

        // Cast for the Transaction `status` column (optional; package uses TransactionStatus enum by default)
        // 'transaction_status' => Eidolex\EWallet\Enums\TransactionStatus::class,

        // Cast for the Transaction `metadata` column
        'transaction_metadata' => 'array',
    ],

    'models' => [
        'wallet' => Eidolex\EWallet\Models\Wallet::class,
        'transaction' => Eidolex\EWallet\Models\Transaction::class,
        'transfer' => Eidolex\EWallet\Models\Transfer::class,
    ],
];
```

### Custom Models

You can swap the package models with your own by extending the base classes and registering them in config. Use this when you need a custom table, extra attributes, or app-specific methods.

**1. Extend the package model** (e.g. custom Wallet):

```php
// app/Models/EWallet.php
namespace App\Models;

use Eidolex\EWallet\Models\Wallet;

class EWallet extends Wallet
{
    protected $table = 'wallets';
}
```

**2. Register in config:**

```php
// config/e-wallet.php
return [
    'models' => [
        'wallet' => App\Models\EWallet::class,
        'transaction' => Eidolex\EWallet\Models\Transaction::class,
        'transfer' => Eidolex\EWallet\Models\Transfer::class,
    ],
    // ...
];
```

**3. Add the trait with matching generics** so static analysis and IDE know the correct types:

```php
// app/Models/User.php
use Eidolex\EWallet\Concerns\HasWallet;
use Eidolex\EWallet\Contracts\HasWalletContract;

class User extends Model implements HasWalletContract
{
    /** @use HasWallet<\Eidolex\EWallet\Enums\TransactionName, \App\Models\EWallet, \Eidolex\EWallet\Models\Transaction, \Eidolex\EWallet\Models\Transfer> */
    use HasWallet;
}
```

Trait generic order: `TName`, `WalletModel`, `TransactionModel`, `TransferModel`. Omit or keep defaults for models you do not customize. If you only use a custom Wallet:

```php
/** @use HasWallet<\Eidolex\EWallet\Enums\TransactionName, \App\Models\EWallet> */
use HasWallet;
```

## Database Schema

The package creates three tables:

**wallets** — One wallet per owner (polymorphic). Unique on `(owner_type, owner_id)`.

| Column | Type |
|---|---|
| `id` | UUID (primary) |
| `owner_type` | string |
| `owner_id` | string |
| `balance` | unsigned big integer (default: 0) |
| `created_at` | timestamp |
| `updated_at` | timestamp |

**transactions** — Individual debit/credit records.

| Column | Type |
|---|---|
| `id` | UUID (primary) |
| `wallet_id` | UUID (foreign key) |
| `type` | unsigned tiny integer (enum: 0 = Withdraw, 1 = Deposit) |
| `name` | string (configurable enum) |
| `amount` | unsigned big integer |
| `status` | unsigned tiny integer (enum: Pending, Completed, Cancelled, Failed, Refunded) |
| `opening_balance` | unsigned big integer (nullable) |
| `closing_balance` | unsigned big integer (nullable) |
| `metadata` | JSON (nullable) |
| `created_at` | timestamp |
| `updated_at` | timestamp |

**transfers** — Links two transactions (sender and receiver).

| Column | Type |
|---|---|
| `id` | UUID (primary) |
| `from_transaction_id` | UUID (foreign key, cascade delete) |
| `to_transaction_id` | UUID (foreign key, cascade delete) |
| `amount` | unsigned big integer |
| `metadata` | JSON (nullable) |
| `created_at` | timestamp |
| `updated_at` | timestamp |

## Transaction Safety

All wallet operations (`topUp`, `withdraw`, `transfer`) are wrapped in `DB::transaction()` so each operation is atomic.

## License

MIT
