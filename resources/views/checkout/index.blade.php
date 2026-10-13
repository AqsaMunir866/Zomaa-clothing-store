<x-app-layout>
    <div class="max-w-5xl mx-auto px-6 py-12 md:py-16">
        <div class="text-center mb-10" data-reveal>
            <p class="eyebrow mb-3">Almost There</p>
            <h1 class="font-serif text-3xl md:text-4xl text-ink">Checkout</h1>
            <div class="topline w-24 mx-auto mt-5"></div>
        </div>

        @if (session('error'))
            <div class="mb-6 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid md:grid-cols-5 gap-8" data-reveal
             x-data="{
                paymentMethod: '{{ $paymentMethods->first()->code ?? '' }}',
                methods: {{ json_encode($paymentMethods->map(fn ($m) => ['code' => $m->code, 'name' => $m->name, 'description' => $m->description, 'details' => $m->details])->values()) }},
                cartItems: {{ json_encode($products->map(fn ($product) => [
                    'title' => $product->title,
                    'price' => $product->price,
                    'quantity' => $cartItems[$product->id]['quantity'],
                ])->values()) }},
                get cartTotal() {
                    return this.cartItems.reduce((sum, item) => sum + item.price * item.quantity, 0)
                },
                selectedMethod() {
                    return this.methods.find(m => m.code === this.paymentMethod)
                }
             }">

            <div class="md:col-span-3 space-y-6">
                <div class="border border-ink/10 bg-white">
                    <div class="border-b border-ink/10 px-6 py-4">
                        <h2 class="font-serif text-xl text-ink">Delivery To</h2>
                    </div>
                    <div class="px-6 py-4 text-sm leading-relaxed text-ink-soft">
                        <p class="text-ink font-medium">{{ $customer->first_name }} {{ $customer->last_name }}</p>
                        <p>{{ $customer->shippingAddress->address1 }}{{ $customer->shippingAddress->address2 ? ', ' . $customer->shippingAddress->address2 : '' }}</p>
                        <p>{{ $customer->shippingAddress->city }}, {{ $customer->shippingAddress->state }}, {{ $customer->shippingAddress->zipcode }}</p>
                        <p>{{ $customer->phone }}</p>
                        <a href="{{ route('profile') }}" class="inline-block mt-2 text-[11px] uppercase tracking-[0.2em] text-clay-500 hover:underline">Change Address</a>
                    </div>
                </div>

                <div class="border border-ink/10 bg-white">
                    <div class="border-b border-ink/10 px-6 py-4">
                        <h2 class="font-serif text-xl text-ink">Payment Method</h2>
                    </div>
                    <div class="px-6 py-4 space-y-3">
                        <template x-for="m of methods" :key="m.code">
                            <label class="block border rounded transition-colors cursor-pointer"
                                   :class="paymentMethod === m.code ? 'border-clay-500 bg-clay-500/5' : 'border-ink/10 hover:border-ink/25'">
                                <div class="flex items-center gap-4 px-5 py-4">
                                    <input type="radio" name="payment_method_pick" x-model="paymentMethod" :value="m.code"
                                           class="focus:ring-clay-500 text-clay-500">
                                    <div>
                                        <p class="font-medium text-ink" x-text="m.name"></p>
                                        <p class="text-sm text-ink-soft" x-text="m.description"></p>
                                    </div>
                                </div>
                                <div x-show="paymentMethod === m.code" x-cloak
                                     class="border-t border-ink/10 px-5 py-4 text-sm leading-relaxed text-ink-soft">
                                    <template x-if="m.details">
                                        <p class="whitespace-pre-line" x-text="m.details"></p>
                                    </template>
                                    <p x-show="!m.details" class="text-ink-soft italic">
                                        You will receive further instructions with your order confirmation.
                                    </p>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2">
                <div class="border border-ink/10 bg-white sticky top-6">
                    <div class="border-b border-ink/10 px-6 py-4">
                        <h2 class="font-serif text-xl text-ink">Your Order</h2>
                    </div>
                    <div class="px-6 py-4">
                        <template x-for="(item, i) of cartItems" :key="i">
                            <div class="flex justify-between items-baseline py-2 text-sm">
                                <span class="text-ink" x-text="item.title + ' × ' + item.quantity"></span>
                                <span class="text-ink-soft" x-text="formatPkr(item.price * item.quantity)"></span>
                            </div>
                        </template>
                        <hr class="border-ink/10 my-3">
                        <div class="flex justify-between items-baseline">
                            <span class="text-[12px] uppercase tracking-[0.2em] text-ink-muted">Total</span>
                            <span class="text-xl text-ink" x-text="formatPkr(cartTotal)"></span>
                        </div>
                        <p class="text-xs text-ink-muted mt-2">Shipping and taxes calculated at checkout.</p>

                        <form action="{{ route('checkout.place') }}" method="post" class="mt-6">
                            @csrf
                            <input type="hidden" name="payment_method" :value="paymentMethod">
                            <button type="submit" class="btn-primary w-full py-4">Place Order</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>