@include('emails.partials.logo')

<h2>New Order Received</h2>
<p>Order {{ $order->order_number }} has been placed.</p>
<p>Total: {{ \App\Support\StoreCurrency::format($order->total_amount) }}</p>
<p>Placed at: {{ \App\Support\StoreDate::dateTime($order->placed_at) }}</p>
