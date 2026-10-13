<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel E-commerce Website') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Jost:wght@300;400;500&display=swap"
        rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>
<body class="bg-white text-ink">
@include('layouts.navigation')

<main>
    {{ $slot }}
</main>

<!-- Footer -->
<footer class="bg-ivory-100 mt-20">
    <div class="topline"></div>
    <div class="max-w-7xl mx-auto px-6 py-14 grid gap-10 md:grid-cols-4">
        <div>
            @php
                $businessName = \App\Support\Settings::businessName();
                $settingsLogo = \App\Support\Settings::logoUrl();
                $tagline = \App\Support\Settings::tagline();
                $address = \App\Support\Settings::get('primary_address');
                $email = \App\Support\Settings::get('email');
                $phone = \App\Support\Settings::get('phone');
            @endphp
            <p class="font-serif text-2xl tracking-wide">
                @if($settingsLogo)
                    <img src="{{ $settingsLogo }}" alt="{{ $businessName }}" class="h-10 w-auto max-w-[160px] object-contain">
                @else
                    {{ $businessName }}
                @endif
            </p>
            <p class="mt-4 text-sm text-ink-muted leading-relaxed">{{ $tagline }}</p>
            <address class="mt-6 not-italic space-y-2 text-sm text-ink-soft">
                @if($address)<p class="leading-relaxed">{{ $address }}</p>@endif
                @if($email)<p><a href="mailto:{{ $email }}" class="hover:text-clay-500 transition-colors">{{ $email }}</a></p>@endif
                @if($phone)<p><a href="tel:{{ $phone }}" class="hover:text-clay-500 transition-colors">{{ $phone }}</a></p>@endif
            </address>
        </div>
        <div>
            <p class="eyebrow mb-4">Shop</p>
            <ul class="space-y-2 text-sm text-ink-soft">
                <li><a href="{{ route('home') }}" class="hover:text-clay-500 transition-colors">New In</a></li>
                <li><a href="{{ route('home') }}" class="hover:text-clay-500 transition-colors">Women</a></li>
                <li><a href="{{ route('home') }}" class="hover:text-clay-500 transition-colors">Men</a></li>
            </ul>
        </div>
        <div>
            <p class="eyebrow mb-4">Account</p>
            <ul class="space-y-2 text-sm text-ink-soft">
                <li><a href="{{ route('profile') }}" class="hover:text-clay-500 transition-colors">My Profile</a></li>
                <li><a href="{{ route('order.index') }}" class="hover:text-clay-500 transition-colors">My Orders</a></li>
                <li><a href="{{ route('cart.index') }}" class="hover:text-clay-500 transition-colors">Cart</a></li>
            </ul>
        </div>
        <div class="space-y-6">
            <div>
                <p class="eyebrow mb-4">Care</p>
                <ul class="space-y-2 text-sm text-ink-soft">
                    <li><a href="#" class="hover:text-clay-500 transition-colors">Delivery &amp; Returns</a></li>
                    <li><a href="#" class="hover:text-clay-500 transition-colors">Contact Us</a></li>
                </ul>
            </div>
            @if($email || $phone)
                <div>
                    <p class="eyebrow mb-4">Get in Touch</p>
                    <ul class="space-y-2 text-sm text-ink-soft">
                        @if($phone)<li><a href="tel:{{ $phone }}" class="hover:text-clay-500 transition-colors">{{ $phone }}</a></li>@endif
                        @if($email)<li><a href="mailto:{{ $email }}" class="hover:text-clay-500 transition-colors">{{ $email }}</a></li>@endif
                    </ul>
                </div>
            @endif
        </div>
    </div>
    <div class="border-t border-ink/10">
        <div class="max-w-7xl mx-auto px-6 py-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-ink-muted tracking-wide">
            <p>&copy; {{ date('Y') }} {{ $businessName }}. All rights reserved.</p>
            <p>Prices are shown in Pakistani Rupees (Rs).</p>
        </div>
    </div>
</footer>

<!-- Toast -->
<div
    x-data="toast"
    x-show="visible"
    x-transition
    x-cloak
    @notify.window="show($event.detail.message, $event.detail.type || 'success')"
    class="fixed w-[400px] left-1/2 -ml-[200px] top-16 py-2 px-4 pb-4 text-white"
    :class="type === 'success' ? 'bg-emerald-500' : 'bg-red-500'"
>
    <div class="font-semibold" x-text="message"></div>
    <button
        @click="close"
        class="absolute flex items-center justify-center right-2 top-2 w-[30px] h-[30px] rounded-full hover:bg-black/10 transition-colors"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M6 18L18 6M6 6l12 12"
            />
        </svg>
    </button>
    <!-- Progress -->
    <div>
        <div
            class="absolute left-0 bottom-0 right-0 h-[6px] bg-black/10"
            :style="{'width': `${percent}%`}"
        ></div>
    </div>
</div>
<!--/ Toast -->
</body>
</html>
