<x-app-layout>

    <div class="container mx-auto lg:w-2/3 p-5">
        <div class="flex items-center justify-between mb-2">
            <h1 class="text-3xl font-bold">Pay for Order #{{$order->id}}</h1>
            <a href="{{ route('order.view', $order) }}" class="text-sm text-purple-600 hover:text-purple-500">
                &larr; Back to order
            </a>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <div class="bg-white rounded-lg p-5">
            <table class="mb-5">
                <tbody>
                <tr>
                    <td class="font-bold py-1 px-2">Order #</td>
                    <td>{{$order->id}}</td>
                </tr>
                <tr>
                    <td class="font-bold py-1 px-2">Order Date</td>
                    <td>{{$order->created_at}}</td>
                </tr>
                <tr>
                    <td class="font-bold py-1 px-2">Order Status</td>
                    <td>
                        <span class="text-white py-1 px-2 rounded {{$order->isPaid() ? 'bg-emerald-500' : 'bg-gray-400'}}">
                            {{$order->status}}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td class="font-bold py-1 px-2">Amount Due</td>
                    <td class="font-semibold">{{ \App\Support\Money::format($order->total_price) }}</td>
                </tr>
                <tr>
                    <td class="font-bold py-1 px-2">Payment Method</td>
                    <td>{{ $paymentMethod->name ?? strtoupper($payment->type) }}</td>
                </tr>
                </tbody>
            </table>

            <hr class="my-5"/>

            @php($code = $payment->type)

            @if ($code === 'cod')
                <div class="rounded border border-amber-200 bg-amber-50 px-5 py-4">
                    <h2 class="text-lg font-semibold mb-1">Cash on Delivery</h2>
                    <p class="text-sm leading-relaxed text-gray-700">
                        No advance payment is required. Please keep
                        <strong>{{ \App\Support\Money::format($order->total_price) }}</strong>
                        ready in cash and pay the rider when your order is delivered.
                    </p>
                </div>
            @else
                <div class="rounded border border-purple-200 bg-purple-50 px-5 py-4">
                    <h2 class="text-lg font-semibold mb-2">
                        Pay via {{ $paymentMethod->name ?? strtoupper($code) }}
                    </h2>
                    @if ($paymentMethod && $paymentMethod->details)
                        <p class="text-sm leading-relaxed text-gray-700 whitespace-pre-line">{{ $paymentMethod->details }}</p>
                    @else
                        <p class="text-sm leading-relaxed text-gray-700">
                            Please contact us for the payment details, then upload your payment receipt below.
                        </p>
                    @endif
                </div>

                <div class="mt-5">
                    @if ($payment->proof_url)
                        <div class="mb-4 flex items-center gap-3 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Payment proof uploaded. Awaiting verification.</span>
                            <a href="{{ $payment->proof_url }}" target="_blank" class="underline underline-offset-2">View</a>
                        </div>
                    @endif

                    <h3 class="font-semibold mb-2">
                        {{ $payment->proof_url ? 'Upload a new proof' : 'Upload your payment proof' }}
                    </h3>
                    <p class="text-sm text-gray-600 mb-3">
                        Attach a screenshot or receipt of your transfer (JPG, PNG, WEBP or PDF, max 8MB).
                        Our team will verify your payment and confirm the order.
                    </p>

                    <form action="{{ route('order.pay.submit', $order) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.webp,.pdf"
                               class="block w-full text-sm text-gray-700 border border-gray-300 rounded p-2 mb-2"/>
                        @error('payment_proof')
                            <p class="text-red-600 text-sm mb-2">{{ $message }}</p>
                        @enderror
                        <button type="submit" class="btn-primary">Submit Payment Proof</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
