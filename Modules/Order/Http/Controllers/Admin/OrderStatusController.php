<?php

namespace Modules\Order\Http\Controllers\Admin;

use Modules\Order\Entities\Order;
use Modules\Order\Entities\OrderProduct;
use Modules\Order\Events\OrderStatusChanged;

class OrderStatusController
{
    /**
     * Update the specified resource in storage.
     *
     * @param \Modules\Order\Entities\Order $request
     * @return \Illuminate\Http\Response
     */
    public function update(Order $order)
    {
        $this->adjustStock($order);

        $order->update(['status' => request('status')]);

        $message = trans('order::messages.status_updated');

        event(new OrderStatusChanged($order));

        return $message;
    }

    private function adjustStock(Order $order)
    {
        if ($this->canceledOrRefunded(request('status'))) {
            $this->restoreStock($order);
        }

        if ($this->canceledOrRefunded($order->status)) {
            $this->reduceStock($order);
        }
    }

    private function canceledOrRefunded($status)
    {
        return in_array($status, [Order::CANCELED, Order::REFUNDED]);
    }

    private function restoreStock(Order $order)
    {
        $order->products->each(function (OrderProduct $orderProduct) {
            if ($orderProduct->product->manage_stock) {
                $orderProduct->product->increment('qty', $orderProduct->qty);
            }

            if ($orderProduct->product->qty === 1) {
                $orderProduct->product->markAsInStock();
            }
        });
    }

    private function reduceStock(Order $order)
    {
        $order->products->each(function (OrderProduct $orderProduct) {
            if (
                $orderProduct->product->manage_stock
                && $orderProduct->product->qty !== 0
            ) {
                $orderProduct->product->decrement('qty', $orderProduct->qty);
            }

            if ($orderProduct->product->qty === 0) {
                $orderProduct->product->markAsOutOfStock();
            }
        });
    }
}
