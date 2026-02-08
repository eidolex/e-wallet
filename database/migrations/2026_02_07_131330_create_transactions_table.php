<?php

use Eidolex\EWallet\Enums\TransactionStatus;
use Eidolex\EWallet\Enums\TransactionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wallet_id')->index();
            $table->unsignedTinyInteger('type')
                ->comment(
                    collect(TransactionType::cases())->map(fn(TransactionType $type) => $type->name)->implode(', ')
                )->index();
            $table->string('name');
            $table->unsignedBigInteger('amount');
            $table->unsignedTinyInteger('status')
                ->comment(
                    collect(TransactionStatus::cases())->map(fn(TransactionStatus $status) => $status->name)->implode(', ')
                )->index();
            $table->unsignedBigInteger('opening_balance')->nullable();
            $table->unsignedBigInteger('closing_balance')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
