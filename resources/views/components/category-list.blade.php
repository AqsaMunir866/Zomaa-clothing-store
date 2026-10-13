@props(['categoryList'])

<div {{ $attributes->merge(['class' => 'category-list flex text-ink']) }}>
    @if (!empty($categoryList))
        @foreach($categoryList as $category)
            <div class="category-item relative">
                <a href="{{ route('byCategory', $category) }}"
                   class="cursor-pointer block py-3 px-6 text-[12px] uppercase tracking-[0.2em] hover:text-clay-500 transition-colors">
                    {{$category->name}}
                </a>
                <x-category-list class="absolute left-0 top-[100%] z-50 hidden flex-col bg-white shadow-lg border border-ink/10"
                                 :category-list="$category->children"/>
            </div>
        @endforeach
    @endif
</div>