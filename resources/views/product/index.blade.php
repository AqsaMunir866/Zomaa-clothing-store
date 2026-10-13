<?php
/** @var \Illuminate\Database\Eloquent\Collection $products */
/** @var \Illuminate\Support\Collection $heroBanners */
/** @var \Illuminate\Support\Collection $splitBanners */
$categoryList = \App\Models\Category::getActiveAsTree();
$catBySlugAll = \App\Models\Category::where('active', true)->get()->keyBy('slug');

// Resolve the image for a category: uploaded (DB) image first, then the on-disk
// seed file (png for generated art, jpg for photos).
$catImg = static function (string $slug) use ($catBySlugAll): string {
    $model = $catBySlugAll->get($slug);
    if ($model && $model->image_url) {
        return $model->image_url;
    }
    $png = public_path('storage/categories/' . $slug . '.png');
    return asset('storage/categories/' . $slug . (file_exists($png) ? '.png' : '.jpg'));
};

$catBySlug = \App\Models\Category::whereIn('slug', ['t-shirts', 'jackets', 'dresses'])->get()->keyBy('slug');

$heroSlides = collect($heroBanners)->map(fn ($banner) => [
    'image'   => $banner->image_url ?: $catImg('t-shirts'),
    'kicker'  => $banner->kicker,
    'title'   => $banner->title,
    'text'    => $banner->text,
    'label'   => $banner->link_label ?: 'Shop Now',
    'href'    => $banner->link_url ?: route('home'),
])->all();

if (empty($heroSlides)) {
    $heroSlides = [
        [
            'image'  => $catImg('t-shirts'),
            'kicker' => 'The Essential Edit',
            'title'  => 'Soft Cotton, Quietly Defined',
            'text'   => 'Airy essentials in neutral tones, made to be lived in.',
            'label'  => 'Shop T-Shirts',
            'href'   => $catBySlug->has('t-shirts') ? route('byCategory', $catBySlug['t-shirts']) : route('home'),
        ],
        [
            'image'  => $catImg('jackets'),
            'kicker' => 'Layer With Intention',
            'title'  => 'Tailored Layers For New Seasons',
            'text'   => 'Structured jackets and relaxed overshirts, cut for effortless layering.',
            'label'  => 'Shop Jackets',
            'href'   => $catBySlug->has('jackets') ? route('byCategory', $catBySlug['jackets']) : route('home'),
        ],
        [
            'image'  => $catImg('dresses'),
            'kicker' => 'The Dress Edit',
            'title'  => 'Fluid Silhouettes In Soft Ivory',
            'text'   => 'Considered cuts that move with you, from day to evening.',
            'label'  => 'Shop Dresses',
            'href'   => $catBySlug->has('dresses') ? route('byCategory', $catBySlug['dresses']) : route('home'),
        ],
    ];
}

$flatCategories = collect();
foreach ($categoryList as $rootCat) {
    $flatCategories->push($rootCat);
    foreach ($rootCat->children ?? [] as $childCat) {
        $flatCategories->push($childCat);
    }
}
?>

<x-app-layout>
    <!-- ===================== HERO ===================== -->
    <section x-data="heroSlider({slides: @js($heroSlides)})" class="relative h-[520px] md:h-[600px] overflow-hidden bg-ivory-100">
        <template x-for="(slide, i) in slides" :key="i">
            <div class="absolute inset-0 hero-slide" :class="{'hero-active': i === index}">
                <img :src="slide.image" :alt="slide.title"
                     class="w-full h-full object-cover"
                     x-show="i === index">
                <div class="absolute inset-0 bg-gradient-to-r from-ivory-50/95 via-ivory-50/50 to-transparent"></div>
                <div class="relative max-w-7xl mx-auto h-full flex items-center px-6">
                    <div class="max-w-lg hero-caption">
                        <p class="eyebrow mb-4" x-text="slide.kicker"></p>
                        <h1 class="font-serif text-4xl md:text-6xl leading-[1.08] text-ink mb-5" x-text="slide.title"></h1>
                        <p class="text-ink-soft leading-relaxed mb-8 max-w-sm" x-text="slide.text"></p>
                        <a :href="slide.href" x-text="slide.label" class="btn-primary"></a>
                    </div>
                </div>
            </div>
        </template>

        <button @click="prev" aria-label="Previous slide"
                class="absolute left-4 md:left-6 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center bg-white/70 backdrop-blur border border-ink/10 text-ink hover:bg-white transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
        <button @click="next" aria-label="Next slide"
                class="absolute right-4 md:right-6 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center bg-white/70 backdrop-blur border border-ink/10 text-ink hover:bg-white transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </button>

        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-2">
            <template x-for="(slide, i) in slides" :key="'dot'+i">
                <button @click="go(i)" :aria-label="`Go to slide ${i + 1}`"
                        class="h-[3px] transition-all duration-300"
                        :class="i === index ? 'w-8 bg-ink' : 'w-4 bg-ink/25 hover:bg-ink/50'">
                </button>
            </template>
        </div>
    </section>

    <!-- ===================== SHOP BY CATEGORY ===================== -->
    <section class="max-w-7xl mx-auto px-6 py-16 md:py-24">
        <div class="text-center mb-10" data-reveal>
            <p class="eyebrow mb-3">Curated for Every Day</p>
            <h2 class="font-serif text-3xl md:text-4xl text-ink">Shop by Category</h2>
            <div class="topline w-24 mx-auto mt-5"></div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-5">
            @foreach($flatCategories as $cat)
                <a href="{{ route('byCategory', $cat) }}" class="category-card group block" data-reveal>
                    <div class="relative aspect-[4/5] overflow-hidden bg-ivory-100">
                        <img src="{{ $catImg($cat->slug) }}" alt="{{ $cat->name }}"
                             loading="lazy"
                             class="category-img w-full h-full object-cover">
                        <div class="absolute inset-0 bg-ink/0 group-hover:bg-ink/10 transition-colors duration-300"></div>
                    </div>
                    <div class="text-center pt-4 pb-2">
                        <p class="font-serif text-xl text-ink">{{ $cat->name }}</p>
                        <span class="category-caption inline-block mt-1 text-[11px] uppercase tracking-[0.24em] text-ink-muted group-hover:text-clay-500">
                            Discover
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

<!-- ===================== SPLIT BANNER ===================== -->
    <section class="bg-ivory-100 py-16 md:py-24">
        <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-2 gap-10 items-center">
            @php
                $splitEdits = collect($splitBanners)
                    ->map(fn ($b) => [
                        'img'   => $b->image_url,
                        'title' => $b->title,
                        'text'  => $b->text,
                        'label' => $b->link_label ?: 'Discover',
                        'href'  => $b->link_url ?: route('home'),
                    ])
                    ->all();

                if (empty($splitEdits) || count($splitEdits) < 2) {
                    $menCategory = $flatCategories->firstWhere('slug', 'men') ?: $flatCategories->first();
                    $womenCategory = $flatCategories->firstWhere('slug', 'women') ?: $flatCategories->first();
                    $splitEdits = [
                        ['img' => $catImg('men'), 'title' => 'The Menswear Edit', 'text' => 'Understated essentials — crisp shirts, washed denim and soft knits.', 'label' => 'Discover', 'href' => $menCategory ? route('byCategory', $menCategory) : route('home')],
                        ['img' => $catImg('women'), 'title' => 'The Womenswear Edit', 'text' => 'Fluid dresses, effortless tops and tailored separates in warm neutrals.', 'label' => 'Discover', 'href' => $womenCategory ? route('byCategory', $womenCategory) : route('home')],
                    ];
                }
            @endphp
            @foreach(array_slice($splitEdits, 0, 2) as $edit)
                <a href="{{ $edit['href'] }}" class="group block" data-reveal>
                    <div class="relative aspect-[16/9] overflow-hidden bg-white">
                        <img src="{{ $edit['img'] }}" alt="{{ $edit['title'] }}" loading="lazy"
                             class="category-img w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/25 to-transparent"></div>
                        <div class="absolute bottom-6 left-6 right-6 text-white">
                            <p class="font-serif text-2xl md:text-3xl">{{ $edit['title'] }}</p>
                            <span class="inline-block mt-2 text-[11px] uppercase tracking-[0.24em] border-b border-white pb-1">
                                {{ $edit['label'] }}
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    <!-- ===================== FEATURED PRODUCTS ===================== -->
    <section class="max-w-7xl mx-auto px-6 py-16 md:py-24">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-10">
            <div data-reveal>
                <p class="eyebrow mb-3">The Latest</p>
                <h2 class="font-serif text-3xl md:text-4xl text-ink">Featured Pieces</h2>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 sm:gap-5 md:pr-2" data-reveal
                 x-data="{
                     selectedSort: '{{ request()->get('sort', '-updated_at') }}',
                     searchKeyword: '{{ request()->get('search') }}',
                     updateUrl() {
                         const params = new URLSearchParams(window.location.search)
                         if (this.selectedSort && this.selectedSort !== '-updated_at') {
                             params.set('sort', this.selectedSort)
                         } else {
                             params.delete('sort')
                         }
                         if (this.searchKeyword) {
                             params.set('search', this.searchKeyword)
                         } else {
                             params.delete('search')
                         }
                         window.location.href = window.location.origin + window.location.pathname + '?' + params.toString();
                     }
                 }">
                <form action="" method="GET" @submit.prevent="updateUrl">
                    <input type="text" name="search" placeholder="Search the collection"
                           x-model="searchKeyword"
                           class="input-line w-56 text-sm bg-transparent"/>
                </form>
                <select x-model="selectedSort" @change="updateUrl" name="sort"
                        class="input-line text-sm uppercase tracking-[0.14em] w-56 bg-transparent cursor-pointer">
                    <option value="price">Price (Low to High)</option>
                    <option value="-price">Price (High to Low)</option>
                    <option value="title">Title (A&ndash;Z)</option>
                    <option value="-title">Title (Z&ndash;A)</option>
                    <option value="-updated_at">Recently Updated</option>
                    <option value="updated_at">Oldest First</option>
                </select>
            </div>
        </div>

        <?php if ($products->count() === 0): ?>
            <div class="text-center text-ink-muted py-24">
                <p class="font-serif text-2xl mb-2">Nothing found</p>
                <p class="text-sm">There are no products published matching your search.</p>
            </div>
        <?php else: ?>
            <div class="grid gap-x-6 gap-y-12 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($products as $product)
                    <div
                        x-data="productItem({{ json_encode([
                            'id' => $product->id,
                            'slug' => $product->slug,
                            'image' => $product->image ?: '/img/noimage.png',
                            'title' => $product->title,
                            'price' => $product->price,
                            'addToCartUrl' => route('cart.add', $product)
                        ]) }})"
                        class="product-card group" data-reveal>
                        <div class="relative aspect-[4/5] overflow-hidden bg-ivory-100 product-media">
                            <a href="{{ route('product.view', $product->slug) }}" class="block h-full">
                                <img :src="product.image" :alt="product.title" loading="lazy"
                                     class="w-full h-full object-cover"/>
                            </a>
                            <div class="product-add absolute bottom-4 inset-x-4">
                                <button @click="addToCart()"
                                        class="w-full bg-white/95 backdrop-blur text-ink py-3 text-[11px] uppercase tracking-[0.22em] border border-ink/10 hover:bg-ink hover:text-white transition-colors">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                        <div class="pt-4 text-center">
                            <a href="{{ route('product.view', $product->slug) }}" class="block">
                                <h3 class="font-serif text-lg text-ink group-hover:text-clay-500 transition-colors">
                                    {{ $product->title }}
                                </h3>
                            </a>
                            <p class="mt-1 text-sm tracking-wide text-ink-soft">
                                {{ \App\Support\Money::format($product->price) }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-14 flex justify-center">
                {{ $products->appends(['sort' => request('sort'), 'search' => request('search')])->links() }}
            </div>
        <?php endif; ?>
    </section>
</x-app-layout>
