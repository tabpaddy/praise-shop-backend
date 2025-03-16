<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Praise Shop Order Receipt</title>
    <style type="text/css">
        body {
            background-color: #f0f4f8;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .container {
            max-width: 650px;
            margin: 30px auto;
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(90deg, #4a00e0, #8e2de2);
            color: #ffffff;
            padding: 25px;
            text-align: center;
            position: relative;
        }
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header p {
            font-size: 14px;
            margin: 8px 0 0;
            opacity: 0.9;
        }
        .header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: #ffffff;
            border-radius: 2px;
        }
        .content {
            padding: 30px;
            background: #ffffff;
        }
        .greeting {
            font-size: 20px;
            color: #1f2937;
            font-weight: 600;
            margin-bottom: 20px;
            border-left: 4px solid #4a00e0;
            padding-left: 12px;
        }
        .content p {
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 20px;
        }
        .summary {
            background: #f9fafb;
            border: 1px dashed #d1d5db;
            border-radius: 10px;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        .summary h2 {
            font-size: 22px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 15px;
            background: #e5e7eb;
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
        }
        .summary p {
            color: #4b5563;
            font-size: 15px;
            margin: 8px 0;
        }
        .summary .label {
            font-weight: 600;
            color: #374151;
        }
        .table-wrapper {
            overflow-x: auto;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        thead tr {
            background: #e5e7eb;
            border-radius: 8px 8px 0 0;
        }
        th {
            padding: 12px;
            text-align: left;
            color: #374151;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #4b5563;
            font-size: 15px;
        }
        tbody tr:last-child td {
            border-bottom: none;
        }
        tbody tr:hover {
            background: #f3f4f6;
        }
        .total-section {
            text-align: right;
            margin-top: 20px;
        }
        .total-shipping, .total {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin: 5px 0;
        }
        .total {
            color: #4a00e0;
            font-size: 20px;
        }
        .contact {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-size: 14px;
            color: #6b7280;
        }
        .contact a {
            color: #4a00e0;
            text-decoration: none;
            font-weight: 600;
        }
        .contact a:hover {
            text-decoration: underline;
        }
        .footer {
            background: #1f2937;
            color: #d1d5db;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            border-top: 3px solid #4a00e0;
        }
        .footer a {
            color: #ffffff;
            text-decoration: none;
            font-weight: 500;
        }
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Praise Shop</h1>
            <p>Your Exclusive Order Receipt</p>
        </div>
        <div class="content">
            <p class="greeting">Greetings {{ $first_name }} {{ $last_name }},</p>
            <p>Thank you for choosing Praise Shop! Below is your dazzling order summary—crafted just for you.</p>

            <div class="summary">
                <h2>Order #{{ $invoice_no }}</h2>
                <p><span class="label">Order Status:</span> {{ ucfirst($order_status) }}</p>
                <p><span class="label">Payment Method:</span> {{ ucfirst($payment_method) }}</p>
                <p><span class="label">Payment Status:</span> {{ ucfirst($payment_status) }}</p>

                @if (isset($items) && !empty($items))
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Size</th>
                                    <th>Price (₦)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr>
                                        <td>{{ $item['name'] }}</td>
                                        <td>{{ $item['quantity'] }}</td>
                                        <td>{{ $item['size'] }}</td>
                                        <td>{{ number_format($item['price'] * $item['quantity'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p>No items found for this order—let’s get shopping!</p>
                @endif

                <div class="total-section">
                    <p class="total-shipping">Shipping: ₦{{ number_format($amount * 0.1 / 1.1, 2) }}</p>
                    <p class="total">Total: ₦{{ number_format($amount, 2) }}</p>
                </div>
            </div>

            <p class="contact">Your order is being prepared with care! Have questions? Reach us at <a href="mailto:taborotap@gmail.com">taborotap@gmail.com</a> or call 09066605427.</p>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} PraiseTheDeveloper • Crafted with Passion</p>
            <p><a href="{{ $url }}">Explore More</a> | <a href="mailto:taborotap@gmail.com">Get in Touch</a></p>
        </div>
    </div>
</body>
</html>