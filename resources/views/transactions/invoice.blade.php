<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ substr($transaction->id, 0, 8) }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            font-size: 14px;
            line-height: 1.5;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 20px;
        }
        .company-info {
            text-align: right;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #4f46e5;
        }
        .invoice-details {
            margin-bottom: 30px;
        }
        .invoice-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .invoice-details-section h3 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #4f46e5;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .invoice-table th {
            background-color: #f3f4f6;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        .invoice-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .invoice-table .item-name {
            width: 50%;
        }
        .invoice-table .text-right {
            text-align: right;
        }
        .invoice-summary {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }
        .invoice-summary-table {
            width: 300px;
        }
        .invoice-summary-table td {
            padding: 8px 0;
        }
        .invoice-summary-table .label {
            font-weight: 600;
        }
        .invoice-summary-table .total {
            font-size: 18px;
            font-weight: bold;
            color: #4f46e5;
        }
        .invoice-notes {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f9fafb;
            border-radius: 6px;
        }
        .invoice-footer {
            text-align: center;
            margin-top: 50px;
            color: #6b7280;
            font-size: 12px;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-paid {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .status-shipped {
            background-color: #e0e7ff;
            color: #4338ca;
        }
        .status-done {
            background-color: #d1fae5;
            color: #065f46;
        }
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .invoice-container {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <div>
                <div class="invoice-title">{{ config('app.name') }}</div>
                <div>Invoice</div>
            </div>
            <div class="company-info">
                <div>Invoice #{{ substr($transaction->id, 0, 8) }}</div>
                <div>Date: {{ $transaction->created_at->format('F j, Y') }}</div>
                <div>
                    @if($transaction->status == 'pending')
                        <span class="status-badge status-pending">Pending</span>
                    @elseif($transaction->status == 'paid')
                        <span class="status-badge status-paid">Paid</span>
                    @elseif($transaction->status == 'shipped')
                        <span class="status-badge status-shipped">Shipped</span>
                    @elseif($transaction->status == 'done')
                        <span class="status-badge status-done">Done</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="invoice-details">
            <div class="invoice-details-grid">
                <div class="invoice-details-section">
                    <h3>Bill To:</h3>
                    <div>{{ $transaction->user->full_name ?? $transaction->user->username }}</div>
                    <div>{{ $transaction->user->email }}</div>
                    @if($transaction->user->address)
                        <div>{{ $transaction->user->address }}</div>
                    @endif
                    @if($transaction->user->city)
                        <div>{{ $transaction->user->city }}</div>
                    @endif
                    @if($transaction->user->contact)
                        <div>{{ $transaction->user->contact }}</div>
                    @endif
                </div>
                <div class="invoice-details-section">
                    <h3>From:</h3>
                    <div>{{ config('app.name') }}</div>
                    <div>123 Health Street</div>
                    <div>Medical District, MD 12345</div>
                    <div>support@medicare.com</div>
                </div>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th class="item-name">Item</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction->details as $detail)
                    <tr>
                        <td>{{ $detail->product->product_name }}</td>
                        <td>${{ number_format($detail->price, 2) }}</td>
                        <td>{{ $detail->amount }}</td>
                        <td class="text-right">${{ number_format($detail->price * $detail->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="invoice-summary">
            <table class="invoice-summary-table">
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="text-right">${{ number_format($transaction->total_price, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Tax:</td>
                    <td class="text-right">$0.00</td>
                </tr>
                <tr>
                    <td class="label">Shipping:</td>
                    <td class="text-right">$0.00</td>
                </tr>
                <tr>
                    <td class="label total">Total:</td>
                    <td class="text-right total">${{ number_format($transaction->total_price, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="invoice-notes">
            <strong>Notes:</strong>
            <p>Thank you for your purchase! If you have any questions about this invoice, please contact our customer support at support@medicare.com.</p>
            
            @if($transaction->status == 'pending')
                <p>This invoice is pending payment. Please complete your payment to process your order.</p>
            @elseif($transaction->status == 'paid')
                <p>Your payment has been received. Your order is being processed and will be shipped soon.</p>
            @elseif($transaction->status == 'shipped')
                <p>Your order has been shipped. Once you receive it, please mark it as received in your account.</p>
            @elseif($transaction->status == 'done')
                <p>This order has been completed. Thank you for shopping with us!</p>
            @endif
        </div>

        <div class="invoice-footer">
            <p>This is a computer-generated document. No signature is required.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
