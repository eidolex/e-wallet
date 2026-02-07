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
    /** @use HasWallet<\Eidolex\EWallet\Enums\TransactionName> */
    use HasWallet;
}
```

This gives the model access to `wallet()`, `transactions()`, `topUp()`, `withdraw()`, and `transfer()`.

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

All operations use [Spatie Laravel Data](https://spatie.be/docs/laravel-data) DTOs. The `name` parameter accepts any `UnitEnum`, so you can use the built-in `TransactionName` or your own enum.

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

### Custom Transformers

Transformers convert DTOs into the array used to create `Transaction` models. You can replace any transformer to add custom logic such as computing `opening_balance`/`closing_balance`, adding fees, or modifying metadata.

#### Step 1: Create a transformer that implements the appropriate contract

```php
namespace App\Transformers;

use Eidolex\EWallet\Contracts\TopUpDataTransformerContract;
use Eidolex\EWallet\Data\TopUpData;

class CustomTopUpDataTransformer implements TopUpDataTransformerContract
{
    public function transform(TopUpData $data): array
    {
        return [
            'name' => $data->name,
            'amount' => $data->amount,
            'status' => $data->status,
            'metadata' => array_merge($data->metadata ?? [], [
                'processed_at' => now()->toISOString(),
                'fee' => (int) ($data->amount * 0.02),
            ]),
        ];
    }
}
```

#### Step 2: Register it in the config

```php
// config/e-wallet.php
'transformers' => [
    'top_up_data' => App\Transformers\CustomTopUpDataTransformer::class,
    // ...
],
```

#### Available Transformer Contracts

| Config Key | Contract | Default Implementation | Purpose |
|---|---|---|---|
| `top_up_data` | `TopUpDataTransformerContract` | `TopUpDataTransformer` | Transforms `TopUpData` for deposit transactions |
| `withdraw_data` | `WithdrawDataTransformerContract` | `WithdrawDataTransformer` | Transforms `WithdrawData` for withdrawal transactions |
| `transfer_from_data` | `TransferDataTransformerContract` | `TransferFromDataTransformer` | Transforms `TransferData` for the sender's withdrawal transaction |
| `transfer_to_data` | `TransferDataTransformerContract` | `TransferToDataTransformer` | Transforms `TransferData` for the receiver's deposit transaction |

> **Note:** Transfer transformers both implement `TransferDataTransformerContract` but are configured separately for the sender (`transfer_from_data`) and receiver (`transfer_to_data`) sides, allowing different logic for each.

#### Transformer Return Array

The array returned by `transform()` is passed directly to the `Transaction` model constructor. The available fields are:

```php
[
    'name'            => UnitEnum,           // required — transaction name
    'amount'          => int,                // required — transaction amount
    'status'          => TransactionStatus,  // required — affects balance update
    'metadata'        => ?array,             // optional
    'opening_balance' => ?int,               // optional — set by your transformer
    'closing_balance' => ?int,               // optional — set by your transformer
]
```

The `type` and `wallet_id` fields are set automatically by the `HasWallet` trait.

## Configuration Reference

```php
// config/e-wallet.php
return [
    'enums' => [
        // Cast for the Transaction `status` column (string or enum class)
        // 'transaction_status' => Eidolex\EWallet\Enums\TransactionStatus::class,

        // Cast for the Transaction `name` column (string or enum class)
        // 'transaction_name' => Eidolex\EWallet\Enums\TransactionName::class,

        // Cast for the Transaction `metadata` column
        'transaction_metadata' => 'array',
    ],

    'transformers' => [
        // Transformer for the sender side of a transfer
        'transfer_from_data' => Eidolex\EWallet\Contracts\TransferDataTransformerContract::class,

        // Transformer for the receiver side of a transfer
        'transfer_to_data' => Eidolex\EWallet\Contracts\TransferDataTransformerContract::class,

        // Transformer for withdrawals
        'withdraw_data' => Eidolex\EWallet\Contracts\WithdrawDataTransformerContract::class,

        // Transformer for top-ups
        'top_up_data' => Eidolex\EWallet\Contracts\TopUpDataTransformerContract::class,
    ],
];
```

## Database Schema

The package creates three tables:

**wallets** — One wallet per owner (polymorphic).

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
| `type` | string (`deposit` / `withdraw`) |
| `name` | string (configurable enum) |
| `amount` | unsigned big integer |
| `status` | string (configurable enum) |
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

All wallet operations (`topUp`, `withdraw`, `transfer`) are wrapped in `DB::transaction()` with 3 automatic retries on deadlock, ensuring atomicity.

## License

MIT
