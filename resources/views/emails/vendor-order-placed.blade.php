<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>New Order Received</title>
</head>
<body>
    <h2>New Order Received</h2>
    <p>Order {{ $order->order_number }} has been placed.</p>
    <p>Total: {{ \App\Support\StoreCurrency::format($order->total_amount) }}</p>
    <p>Placed at: {{ optional($order->placed_at)->format('d M Y, h:i A') }}</p>
</body>
</html>
