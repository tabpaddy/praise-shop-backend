<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Praise Shop Order Receipt</title>
    {{-- <script src="https://cdn.tailwindcss.com"></script> --}}
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-100 font-sans">
    <div class="max-w-lg mx-auto my-5 bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-gray-900 text-white p-5 text-center">
            <h1 class="text-2xl font-bold">Praise Shop</h1>
            <p class="text-sm">Your Order Receipt</p>
        </div>
        <div class="p-5">
            <p class="text-lg text-gray-800 mb-4">Hello {{ $first_name }} {{ $last_name }},</p>
            <p class="text-gray-600 mb-4">Thank you for shopping with Praise Shop! Here’s your order summary:</p>

            <div class="border border-gray-200 rounded-md p-4 bg-gray-50">
                <h2 class="text-xl font-semibold text-gray-900">Order #{{ $invoice_no }}</h2>
                <p class="text-gray-600 mt-2"><span class="font-medium">Order Status:</span> {{ ucfirst($order_status) }}</p>
                <p class="text-gray-600"><span class="font-medium">Payment Method:</span> {{ ucfirst($payment_method) }}</p>
                <p class="text-gray-600"><span class="font-medium">Payment Status:</span> {{ ucfirst($payment_status) }}</p>

                <table class="w-full mt-4 border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-2 text-left text-gray-700 font-medium">Item</th>
                            <th class="p-2 text-left text-gray-700 font-medium">Qty</th>
                            <th class="p-2 text-left text-gray-700 font-medium">Size</th>
                            <th class="p-2 text-left text-gray-700 font-medium">Price (₦)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td class="p-2 border-b border-gray-200">{{ $item['name'] }}</td>
                                <td class="p-2 border-b border-gray-200">{{ $item['quantity'] }}</td>
                                <td class="p-2 border-b border-gray-200">{{ $item['size'] }}</td>
                                <td class="p-2 border-b border-gray-200">{{ number_format($item['price'] * $item['quantity'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <p class="text-lg font-bold text-gray-900 text-right mt-4">Shipping: ₦{{ number_format($amount * 0.1 / 1.1, 2) }}</p>
                <p class="text-lg font-bold text-gray-900 text-right">Total: ₦{{ number_format($amount, 2) }}</p>
            </div>

            <p class="text-gray-600 mt-4">We’ll notify you once your order is on its way. Questions? Contact us at <a href="mailto:taborotap@gmail.com" class="text-gray-900 hover:underline">taborotap@gmail.com</a> or 09066605427.</p>
        </div>
        <div class="bg-gray-200 p-4 text-center text-gray-600 text-sm">
            <p>© {{ date('Y') }} PraiseTheDeveloper. All rights reserved.</p>
            <p><a href="{{ url('/') }}" class="text-gray-900 hover:underline">Visit our website</a> | <a href="mailto:taborotap@gmail.com" class="text-gray-900 hover:underline">Contact Us</a></p>
        </div>
    </div>
</body>
</html>