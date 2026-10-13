<x-app-layout>
    <div class="max-w-2xl mx-auto px-6 py-16 md:py-24 text-center" data-reveal>
        <div class="w-16 h-16 mx-auto mb-6 rounded-full bg-green-100 flex items-center justify-center">
            <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <p class="eyebrow mb-3">Order Confirmed</p>
        <h1 class="font-serif text-3xl md:text-4xl text-ink mb-4">Thank you for your order!</h1>
        <p class="text-ink-soft leading-relaxed mb-8">
            Your order <span class="font-medium text-ink">#{{ $order->id }}</span>
            has been placed successfully. We have sent a confirmation to your email.
        </p>

        <div class="border border-ink/10 bg-white text-left mb-8">
            <div class="border-b border-ink/10 px-6 py-4">
                <h2 class="font-serif text-xl text-ink">Order Summary</h2>
            </div>
            <div class="px-6 py-4">
                <div class="flex justify-between py-1.5 text-sm">
                    <span class="text-ink-soft">Order Number</span>
                    <span class="text-ink font-medium">#{{ $order->id }}</span>
                </div>
                <div class="flex justify-between py-1.5 text-sm">
                    <span class="text-ink-soft">Date</span>
                    <span class="text-ink">{{ $order->created_at->format('d M Y, h:i A') }}</span>
                </div>
                <div class="flex justify-between py-1.5 text-sm">
                    <span class="text-ink-soft">Payment Method</span>
                    <span class="text-ink font-medium">{{ $paymentMethod->name ?? 'Cash on Delivery' }}</span>
                </div>
                <div class="flex justify-between py-1.5 text-sm">
                    <span class="text-ink-soft">Total</span>
                    <span class="text-ink font-medium">{{ number_format($order->total_price) }} PKR</span>
                </div>
            </div>
        </div>

        @if ($paymentMethod && $paymentMethod->details)
            <div class="border border-clay-500/30 bg-clay-500/5 text-left px-6 py-5 mb-8">
                <p class="text-[11px] uppercase tracking-[0.24em] text-clay-600 mb-2">How to pay</p>
                <p class="text-sm leading-relaxed text-ink-soft whitespace-pre-line">{{ $paymentMethod->details }}</p>
            </div>
        @endif

        <p class="text-sm text-ink-muted mb-8">
            You can track the status of your order any time from your
            <a href="{{ route('order.index') }}" class="underline underline-offset-2 hover:text-clay-500">orders page</a>.
        </p>

        <a href="{{ route('home') }}" class="btn-ghost">Continue Shopping</a>
    </div>
</x-app-layout>