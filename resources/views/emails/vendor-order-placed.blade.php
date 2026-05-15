<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>New Order Received</title>
</head>
<body>
    @include('emails.partials.logo')
    <h2>New Order Received</h2>
    <p>Order {{ $order->order_number }} has been placed.</p>
    <p>Total: {{ \App\Support\StoreCurrency::format($order->total_amount) }}</p>
    <p>Placed at: {{ \App\Support\StoreDate::dateTime($order->placed_at) }}</p>
</body>
</html>
