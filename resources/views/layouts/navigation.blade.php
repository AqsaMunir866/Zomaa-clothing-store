<?php
/** @var \Illuminate\Support\Collection $navCategories */
$navCategories = \App\Models\Category::getActiveAsTree();
$businessName = \App\Support\Settings::businessName();
$settingsLogo = \App\Support\Settings::logoUrl();
?>

<header class="bg-white relative z-50">
    <!-- Announcement bar -->
    <div class="bg-ink text-white text-[11px] tracking-[0.24em] uppercase text-center py-2 px-4">
        {{ \App\Support\Settings::announcement() }}
    </div>

    <div
        x-data="{
            mobileMenuOpen: false,
            cartItemsCount: {{ \App\Helpers\Cart::getCartItemsCount() }},
        }"
        @cart-change.window="cartItemsCount = $event.detail.count"
        class="border-b border-ink/10"
    >
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between gap-4">

            <!-- Mobile burger -->
            <button @click="mobileMenuOpen = true" class="md:hidden p-1 -ml-1 text-ink" aria-label="Open menu">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

<!-- Logo -->
            <a href="{{ route('home') }}" class="whitespace-nowrap">
                @if($settingsLogo)
                    <img src="{{ $settingsLogo }}" alt="{{ $businessName }}" class="h-10 w-auto max-w-[180px] object-contain">
                @else
                    <span class="font-serif text-2xl md:text-[28px] tracking-[0.18em] text-ink">{{ $businessName }}</span>
                @endif
            </a>

            <!-- Desktop nav -->
            <nav class="hidden md:flex items-center gap-1 mx-auto">
                <a href="{{ route('home') }}"
                   class="px-5 py-2 text-[12px] uppercase tracking-[0.2em] border-b border-transparent hover:border-clay-400 transition-colors {{ request()->routeIs('home') ? 'border-ink text-ink' : 'text-ink-soft' }}">
                    New In
                </a>
                @foreach($navCategories as $navCat)
                    <div class="relative" x-data="{open: false}" @mouseenter="open = true" @mouseleave="open = false">
                        <a href="{{ route('byCategory', $navCat) }}"
                           @click="open = !open"
                           class="px-5 py-2 inline-flex items-center gap-1 text-[12px] uppercase tracking-[0.2em] border-b border-transparent hover:border-clay-400 transition-colors text-ink-soft">
                            {{ $navCat->name }}
                            @if(!empty($navCat->children))
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                                </svg>
                            @endif
                        </a>
                        @if(!empty($navCat->children))
                            <ul x-cloak x-show="open" x-transition.origin.top class="absolute top-full left-0 w-56 bg-white border border-ink/10 shadow-lg py-2">
                                @foreach($navCat->children ?? [] as $child)
                                    <li>
                                        <a href="{{ route('byCategory', $child) }}"
                                           class="block px-5 py-2.5 text-sm text-ink-soft hover:text-ink hover:bg-ivory-100 transition-colors">
                                            {{ $child->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </nav>

            <!-- Right actions -->
            <div class="flex items-center gap-3 md:gap-5">

                <!-- Search -->
                <a href="{{ route('home') }}" class="hidden md:block p-1 text-ink hover:text-clay-500 transition-colors" aria-label="Search">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </a>

                <!-- Cart -->
                <a href="{{ route('cart.index') }}" class="relative p-1 text-ink hover:text-clay-500 transition-colors" aria-label="Cart">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/>
                    </svg>
                    <small x-show="cartItemsCount" x-transition x-cloak x-text="cartItemsCount"
                           class="absolute -top-1.5 -right-2 min-w-[18px] h-[18px] px-1 rounded-full bg-clay-400 text-white text-[10px] font-medium flex items-center justify-center">
                    </small>
                </a>

                <!-- Account -->
                @if (!Auth::guest())
                    <div class="hidden md:relative md:flex" x-data="{open: false}" @click.outside="open = false">
                        <button @click="open = !open" class="p-1 text-ink hover:text-clay-500 transition-colors" aria-label="My account">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </button>
                        <ul x-cloak x-show="open" x-transition.origin.top class="absolute top-[calc(100%+10px)] right-0 w-52 bg-white border border-ink/10 shadow-lg py-2">
                            <li>
                                <a href="{{ route('profile') }}" class="block px-5 py-2.5 text-sm text-ink-soft hover:text-ink hover:bg-ivory-100 transition-colors">My Profile</a>
                            </li>
                            <li>
                                <a href="{{ route('order.index') }}" class="block px-5 py-2.5 text-sm text-ink-soft hover:text-ink hover:bg-ivory-100 transition-colors">My Orders</a>
                            </li>
                            <li class="border-t border-ink/5 mt-1 pt-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <a href="{{ route('logout') }}"
                                       onclick="event.preventDefault(); this.closest('form').submit();"
                                       class="block px-5 py-2.5 text-sm text-ink-soft hover:text-ink hover:bg-ivory-100 transition-colors">
                                        Log Out
                                    </a>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <div class="hidden md:flex items-center gap-5">
                        <a href="{{ route('login') }}" class="text-[12px] uppercase tracking-[0.2em] text-ink-soft hover:text-ink transition-colors">
                            Sign In
                        </a>
                        <a href="{{ route('register') }}" class="btn-primary !py-2.5 !px-5">
                            Register
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Mobile drawer -->
        <div x-cloak x-show="mobileMenuOpen" x-transition.opacity
             class="fixed inset-0 z-[60] bg-black/40 lg:hidden" @click="mobileMenuOpen = false"></div>
        <aside x-cloak x-show="mobileMenuOpen"
               x-transition:enter="transition ease-out duration-300"
               x-transition:enter-start="-translate-x-full"
               x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-200"
               x-transition:leave-start="translate-x-0"
               x-transition:leave-end="-translate-x-full"
               class="fixed z-[70] top-0 left-0 h-full w-[300px] bg-white shadow-xl lg:hidden overflow-y-auto">
<div class="flex items-center justify-between px-6 h-20 border-b border-ink/10">
                @if($settingsLogo)
                    <img src="{{ $settingsLogo }}" alt="{{ $businessName }}" class="h-9 w-auto max-w-[140px] object-contain">
                @else
                    <span class="font-serif text-xl tracking-[0.18em]">{{ $businessName }}</span>
                @endif
                <button @click="mobileMenuOpen = false" class="p-1 text-ink" aria-label="Close menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <nav class="px-6 py-4 space-y-1">
                <a href="{{ route('home') }}" @click="mobileMenuOpen = false"
                   class="block py-3 text-[12px] uppercase tracking-[0.2em] text-ink border-b border-ink/5">
                    New In
                </a>
                @foreach($navCategories as $navCat)
                    <div x-data="{open: false}">
                        <button @click="open = !open"
                                class="w-full flex items-center justify-between py-3 text-[12px] uppercase tracking-[0.2em] text-ink border-b border-ink/5">
                            {{ $navCat->name }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                            </svg>
                        </button>
                        <ul x-show="open" x-collapse>
                            @foreach($navCat->children ?? [] as $child)
                                <li>
                                    <a href="{{ route('byCategory', $child) }}" @click="mobileMenuOpen = false"
                                       class="block py-2.5 pl-4 text-sm text-ink-soft">
                                        {{ $child->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>
            <div class="px-6 py-4 border-t border-ink/10">
                @if(!Auth::guest())
                    <a href="{{ route('profile') }}" @click="mobileMenuOpen = false" class="block py-2 text-sm text-ink-soft">My Profile</a>
                    <a href="{{ route('order.index') }}" @click="mobileMenuOpen = false" class="block py-2 text-sm text-ink-soft">My Orders</a>
                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="text-sm text-ink-muted underline underline-offset-2">Log Out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn-primary w-full justify-center mb-3">Sign In</a>
                    <a href="{{ route('register') }}" class="btn-ghost w-full justify-center">Create Account</a>
                @endif
            </div>
        </aside>
    </div>
</header>
