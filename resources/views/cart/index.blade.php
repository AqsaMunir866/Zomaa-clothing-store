<x-app-layout>
    <div class="max-w-5xl mx-auto px-6 py-12 md:py-16">
        <div class="text-center mb-10" data-reveal>
            <p class="eyebrow mb-3">Your Selection</p>
            <h1 class="font-serif text-3xl md:text-4xl text-ink">Shopping Bag</h1>
            <div class="topline w-24 mx-auto mt-5"></div>
        </div>

        <div x-data="{
            cartItems: {{
                json_encode(
                    $products->map(fn($product) => [
                        'id' => $product->id,
                        'slug' => $product->slug,
                        'image' => $product->image ?: '/img/noimage.png',
                        'title' => $product->title,
                        'price' => $product->price,
                        'quantity' => $cartItems[$product->id]['quantity'],
                        'href' => route('product.view', $product->slug),
                        'removeUrl' => route('cart.remove', $product),
                        'updateQuantityUrl' => route('cart.update-quantity', $product)
                    ])
                )
            }},
            get cartTotal() {
                return this.cartItems.reduce((accum, next) => accum + next.price * next.quantity, 0)
            },
        }" data-reveal>
            <template x-if="cartItems.length">
                <div class="border border-ink/10 bg-white">
                    <template x-for="product of cartItems" :key="product.id">
                        <div x-data="productItem(product)">
                            <div class="flex flex-col sm:flex-row items-center gap-6 p-6">
                                <a :href="product.href" class="shrink-0 w-32 h-40 bg-ivory-100 flex items-center justify-center overflow-hidden p-3">
                                    <img :src="product.image" :alt="product.title" class="max-h-full max-w-full object-contain mix-blend-multiply"/>
                                </a>
                                <div class="flex-1 w-full text-center sm:text-left">
                                    <div class="mb-3">
                                        <a :href="product.href">
                                            <h3 class="font-serif text-xl text-ink hover:text-clay-500 transition-colors" x-text="product.title"></h3>
                                        </a>
                                        <span class="block mt-1 text-sm tracking-wide text-ink-soft" x-text="formatPkr(product.price)"></span>
                                    </div>
                                    <div class="flex items-center justify-center sm:justify-between gap-6">
                                        <div class="flex items-center border border-ink/15">
                                            <button type="button"
                                                    @click="product.quantity = Math.max(1, product.quantity - 1); changeQuantity()"
                                                    class="px-4 py-2.5 text-ink hover:bg-ivory-100 transition-colors">−</button>
                                            <input type="number" min="1" x-model="product.quantity"
                                                   @change="changeQuantity()"
                                                   class="w-14 text-center text-sm border-0 focus:ring-0"/>
                                            <button type="button"
                                                    @click="product.quantity += 1; changeQuantity()"
                                                    class="px-4 py-2.5 text-ink hover:bg-ivory-100 transition-colors">+</button>
                                        </div>
                                        <a href="#" @click.prevent="removeItemFromCart()"
                                           class="text-[11px] uppercase tracking-[0.2em] text-ink-muted hover:text-ink underline underline-offset-2 transition-colors">
                                            Remove
                                        </a>
                                    </div>
                                </div>
                                <div class="text-right w-28 shrink-0">
                                    <span class="text-lg tracking-wide text-ink" x-text="formatPkr(product.price * product.quantity)"></span>
                                </div>
                            </div>
                            <hr class="border-ink/5">
                        </div>
                    </template>

                    <div class="p-6 bg-ivory-50 flex flex-col items-end">
                        <div class="w-full max-w-xs">
                            <div class="flex justify-between items-baseline mb-1">
                                <span class="text-[12px] uppercase tracking-[0.2em] text-ink-muted">Subtotal</span>
                                <span class="text-xl text-ink" x-text="formatPkr(cartTotal)"></span>
                            </div>
                            <p class="text-xs text-ink-muted mb-6">
                                Shipping and taxes calculated at checkout.
                            </p>
                            <a href="{{ route('checkout') }}" class="btn-primary w-full py-4 block text-center">Proceed to Checkout</a>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="!cartItems.length">
                <div class="text-center py-20 border border-dashed border-ink/20">
                    <p class="font-serif text-2xl mb-3">Your bag is empty</p>
                    <p class="text-sm text-ink-muted mb-8">Discover pieces to love from our latest collection.</p>
                    <a href="{{ route('home') }}" class="btn-ghost">Continue Shopping</a>
                </div>
            </template>
        </div>
    </div>
</x-app-layout>