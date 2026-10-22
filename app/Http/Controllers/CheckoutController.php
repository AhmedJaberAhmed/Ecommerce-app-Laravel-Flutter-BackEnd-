<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    // Create a new order
    public function store(Request $request)
    {
        $data = $request->validate([
            'payment_method' => 'required|in:card,cod',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        // Start with COD only.
        if ($data['payment_method'] !== 'cod') {
            return response()->json([
                'message' => 'Card payments are not configured yet.'
            ], 422);
        }

        $order = DB::transaction(function () use ($data, $request) {

            $quantities = collect($data['items'])
                ->groupBy('product_id')
                ->map(fn ($rows) => $rows->sum('qty'));

            $products = Product::whereIn('id', $quantities->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $order = Order::create([
                'user_id' => $request->user()->id,
                'status' => 'confirmed',
                'payment_method' => 'cod',
                'total_cents' => 0,
                'phone' => $data['phone'],
                'address' => $data['address'],
                'city' => $data['city'],
            ]);

            $total = 0;

            foreach ($quantities as $productId => $qty) {
                $product = $products[$productId];

                if ($product->stock < $qty) {
                    abort(422, "Not enough stock for {$product->name}");
                }

                $priceCents = (int) round($product->price * 100);

                $order->items()->create([
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'price_cents' => $priceCents,
                    'qty' => $qty,
                ]);

                $total += $priceCents * $qty;

                $product->decrement('stock', $qty);
            }

            $order->update([
                'total_cents' => $total
            ]);

            return $order;
        });

        return response()->json([
            'order_id' => $order->id,
            'status' => $order->status,
            'payment_method' => 'cod',
            'checkout_url' => null,
        ], 201);
    }

    // Get order status
    public function show(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        return response()->json([
            'order_id' => $order->id,
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'total_cents' => $order->total_cents,
        ]);
    }

    // Paymob webhook will be implemented later
    public function webhook(Request $request)
    {
        return response()->json([
            'message' => 'Paymob is not configured yet.'
        ], 501);
    }
}
