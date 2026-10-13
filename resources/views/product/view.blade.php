<x-app-layout>
    <div x-data="productItem({{ json_encode([
                    'id' => $product->id,
                    'slug' => $product->slug,
                    'image' => $product->image ?: '/img/noimage.png',
                    'title' => $product->title,
                    'price' => $product->price,
                    'quantity' => $product->quantity,
                    'addToCartUrl' => route('cart.add', $product)
                ]) }})"
         class="max-w-7xl mx-auto px-6 py-10 md:py-16">
        <nav class="text-[11px] uppercase tracking-[0.2em] text-ink-muted mb-8">
            <a href="{{ route('home') }}" class="hover:text-ink transition-colors">Home</a>
            <span class="mx-2">/</span>
            <span class="text-ink-soft">{{ $product->title }}</span>
        </nav>

        <div class="grid gap-10 lg:grid-cols-2">
            <!-- Gallery -->
            <div
                x-data="{
                  images: {{ $product->images->count() ? $product->images->map(fn($im) => $im->url) : json_encode(['/img/noimage.png']) }},
                  activeImage: null,
                  prev() {
                      let index = this.images.indexOf(this.activeImage);
                      if (index === 0) index = this.images.length;
                      this.activeImage = this.images[index - 1];
                  },
                  next() {
                      let index = this.images.indexOf(this.activeImage);
                      if (index === this.images.length - 1) index = -1;
                      this.activeImage = this.images[index + 1];
                  },
                  init() {
                      this.activeImage = this.images.length > 0 ? this.images[0] : null
                  }
                }"
                data-reveal>
                <div class="relative aspect-[4/5] bg-ivory-100 overflow-hidden">
                    <template x-for="image in images" :key="image">
                        <div x-show="activeImage === image" class="absolute inset-0 flex items-center justify-center p-10">
                            <img :src="image" :alt="'{{ $product->title }}'" class="max-h-full w-auto h-full object-contain mix-blend-multiply"/>
                        </div>
                    </template>
                    <button @click="prev" aria-label="Previous image"
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center bg-white/80 border border-ink/10 text-ink hover:bg-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <button @click="next" aria-label="Next image"
                            class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center bg-white/80 border border-ink/10 text-ink hover:bg-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
                <div class="flex gap-3 mt-4">
                    <template x-for="image in images" :key="'t'+image">
                        <button @click="activeImage = image"
                                class="w-20 h-24 border bg-ivory-100 p-2 flex items-center justify-center transition-colors"
                                :class="activeImage === image ? 'border-ink' : 'border-transparent'">
                            <img :src="image" :alt="'{{ $product->title }}'" class="max-h-full max-w-full object-contain mix-blend-multiply"/>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Details -->
            <div class="lg:pl-8" data-reveal>
                <p class="eyebrow mb-3">{{ config('app.name', 'Maison Blanc') }}</p>
                <h1 class="font-serif text-3xl md:text-4xl text-ink mb-4 leading-tight">
                    {{ $product->title }}
                </h1>

                <div class="flex items-center gap-4 mb-8">
                    <span class="text-2xl tracking-wide text-ink">
                        {{ \App\Support\Money::format($product->price) }}
                    </span>
                    @if ($product->quantity > 0)
                        <span class="text-[11px] uppercase tracking-[0.2em] text-ink-muted">
                            In stock
                        </span>
                    @endif
                </div>

                @if ($product->quantity === 0)
                    <div class="bg-ivory-200 text-ink-soft py-3 px-4 mb-6 text-sm">
                        This product is currently out of stock.
                    </div>
                @endif

                <div class="flex items-center gap-4 mb-8">
                    <label for="quantity" class="text-[11px] uppercase tracking-[0.2em] text-ink-muted">
                        Quantity
                    </label>
                    <div class="flex items-center border border-ink/15">
                        <button type="button"
                                @click="$refs.quantityEl.value = Math.max(1, (parseInt($refs.quantityEl.value) || 1) - 1)"
                                class="px-4 py-3 text-ink hover:bg-ivory-100 transition-colors">−</button>
                        <input id="quantity" type="number" name="quantity" x-ref="quantityEl" value="1" min="1"
                               class="w-16 text-center border-0 text-sm focus:ring-0"/>
                        <button type="button"
                                @click="$refs.quantityEl.value = (parseInt($refs.quantityEl.value) || 1) + 1"
                                class="px-4 py-3 text-ink hover:bg-ivory-100 transition-colors">+</button>
                    </div>
                </div>

                <button
                    :disabled="product.quantity === 0"
                    @click="addToCart($refs.quantityEl.value)"
                    class="btn-primary w-full py-4 mb-4"
                    :class="product.quantity === 0 ? 'opacity-40 cursor-not-allowed' : ''"
                >
                    Add to Bag
                </button>
                <p class="text-center text-xs text-ink-muted mb-10">
                    Complimentary delivery on orders over Rs 5,000 &middot; Easy 30-day returns
                </p>

                <div class="border-t border-ink/10" x-data="{expanded: false}">
                    <button @click="expanded = !expanded"
                            class="w-full flex items-center justify-between py-5 text-[12px] uppercase tracking-[0.22em] text-ink">
                        Description
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" :class="expanded && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                        </svg>
                    </button>
                    <div x-show="expanded" x-collapse class="pb-5 text-sm text-ink-soft leading-relaxed wysiwyg-content">
                        {!! $product->description !!}
                    </div>
                </div>

                <div class="border-t border-ink/10 py-5 flex flex-col sm:flex-row gap-3 sm:gap-8 text-[12px] uppercase tracking-[0.2em] text-ink-muted">
                    <span>Free shipping over Rs 5,000</span>
                    <span>Easy 30-day returns</span>
                    <span>Ethically made</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>