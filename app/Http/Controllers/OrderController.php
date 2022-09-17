<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function getMidtransSnapUrl($params)
    {
        \Midtrans\Config::$serverKey = \env("MIDTRANS_SERVER_KEY");
        \Midtrans\Config::$isProduction = (bool) \env("MIDTRANS_IS_PRODUCTION");
        \Midtrans\Config::$is3ds = (bool) \env("MIDTRANS_IS_3DS");

        // \Midtrans\Config::$overrideNotifUrl = "https://3ef1-180-248-0-44.ap.ngrok.io/api/v1/notifications";
        $trx = \Midtrans\Snap::createTransaction($params);
        $snapURL = $trx->redirect_url;
        return $snapURL;
    }


    public function index(Request $request)
    {
        $userIds = $request->query("user_id");
        $orders = Order::query();

        $orders->when($userIds, function ($query) use ($userIds) {
            $query->where("user_id", "=", $userIds);
        });

        return \response()->json([
            "status" => "success",
            "message" => "berhasil mendapatkan data orders.",
            "data" => $orders->get()
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->input("user");
        $course = $request->input("course");

        $order = Order::create([
            "user_id" => $user["id"],
            "course_id" => $course["id"]
        ]);


        $transactionDetails = [
            "order_id" => $order->id . '-' . Str::random(),
            "gross_amount" => $course["price"],
        ];

        $itemDetails = [
            [
                "id" => $course["id"],
                "price" => $course["price"],
                "quantity" => 1,
                "name" => $course["name"],
                "brand" => "PT Triadi",
                "category" => "online course",
            ]
        ];

        $customerDetails = [
            "name" => $user["name"],
            "email" => $user["email"]
        ];

        $midtransParams = [
            "transaction_details" => $transactionDetails,
            "item_details"  => $itemDetails,
            "customer_details" => $customerDetails
        ];
        $snapURL = $this->getMidtransSnapUrl($midtransParams);
        $order->snap_url = $snapURL;
        $order->metadata = [
            "course_id" => $course['id'],
            "course_name" => $course["name"],
            "course_price" => $course["price"],
            "course_thumbnail" => $course['thumbnail'],
            "course_level"  => $course['level']
        ];

        $order->save();

        return \response()->json([
            "status" => "success",
            "message" => "Order has been created.",
            "data" => $order
        ], 201);
    }
}
