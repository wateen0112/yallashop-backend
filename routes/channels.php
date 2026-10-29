<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    $order = \App\Modules\Orders\Models\Order::find($orderId);
    if (!$order) return false;
    // Customer can view their own order, vendor can view if store matches, courier assigned
    return (int) $user->id === (int) $order->customer_id
        || $user->hasRole('admin')
        || ($user->hasRole('vendor') && $order->subOrders()->whereHas('store', fn($q) => $q->where('owner_user_id', $user->id))->exists())
        || ($user->hasRole('courier') && $order->subOrders()->where('courier_id', $user->id)->exists());
});

Broadcast::channel('store.{storeId}', function ($user, $storeId) {
    if (!$user->hasRole('vendor')) return false;
    return $user->store?->id === (int) $storeId;
});

Broadcast::channel('courier.{courierId}', function ($user, $courierId) {
    return (int) $user->id === (int) $courierId;
});