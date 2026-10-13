<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('description')->nullable();
            $table->text('details')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        $defaults = [
            ['Cash on Delivery', 'cod', 'Pay in cash when your order arrives at your doorstep.', 'No advance payment needed. Please keep the exact amount ready at the time of delivery.'],
            ['Bank Transfer', 'bank_transfer', 'Transfer the total amount to our bank account. Your order is confirmed once the payment is received.', "Bank: [Bank Name]\nAccount Title: [Account Title]\nIBAN / Account #: [Account Number]\nPlease use your Order # as the payment reference."],
            ['JazzCash', 'jazzcash', 'Pay securely through your JazzCash mobile wallet.', "JazzCash Mobile Number: 0300-0000000\nPlease enter your Order # as the reference when sending the payment."],
            ['EasyPaisa', 'easypaisa', 'Pay securely through your EasyPaisa wallet.', "EasyPaisa Mobile Number: 0300-0000000\nPlease enter your Order # as the reference when sending the payment."],
        ];

        foreach ($defaults as $i => $method) {
            DB::table('payment_methods')->insert([
                'name' => $method[0],
                'code' => $method[1],
                'description' => $method[2],
                'details' => $method[3],
                'is_active' => true,
                'sort_order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};