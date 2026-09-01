<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transaction Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4F46E5;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .button {
            display: inline-block;
            background-color: #4F46E5;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Medicare</h1>
        <p>Transaction Confirmation</p>
    </div>
    
    <div class="content">
        <p>Dear {{ $transaction->user->full_name ?? $transaction->user->username }},</p>
        
        <p>Thank you for your order. Your transaction has been created successfully.</p>
        
        <p><strong>Transaction ID:</strong> {{ $transaction->id }}</p>
        <p><strong>Date:</strong> {{ $transaction->created_at->format('F j, Y, g:i a') }}</p>
        <p><strong>Status:</strong> {{ ucfirst($transaction->status) }}</p>
        <p><strong>Total Amount:</strong> ${{ number_format($transaction->total_price, 2) }}</p>
        
        <h3>Order Details:</h3>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction->details as $detail)
                <tr>
                    <td>{{ $detail->product->product_name }}</td>
                    <td>{{ $detail->amount }}</td>
                    <td>${{ number_format($detail->price, 2) }}</td>
                    <td>${{ number_format($detail->price * $detail->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                    <td><strong>${{ number_format($transaction->total_price, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
        
        <p>Please click the button below to proceed with payment:</p>
        
        <a href="{{ route('transactions.show', $transaction) }}" class="button">View Transaction & Pay</a>
        
        <p>If you have any questions or concerns, please don't hesitate to contact us.</p>
        
        <p>Best regards,<br>Medicare Team</p>
    </div>
    
    <div class="footer">
        <p>This email was sent to {{ $transaction->user->email }}. If you did not make this transaction, please contact us immediately.</p>
        <p>&copy; {{ date('Y') }} Medicare. All rights reserved.</p>
    </div>
</body>
</html>
