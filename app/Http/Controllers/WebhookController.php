<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentLog;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function midtransHandler(Request $request)
    {
        $data = $request->all();
        $signatureKey = $data["signature_key"];
        $orderId = $data["order_id"];
        $grossAmount = $data["gross_amount"];
        $statusCode = $data["status_code"];
        $serverKey = \env("MIDTRANS_SERVER_KEY");

        $validSignatureKey =  hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if ($validSignatureKey !== $signatureKey) {
            return \response()->json([
                "status" => "error",
                "message" => "Signaturekey not valid."
            ], 400);
        }
        $transactionStatus = $data["transaction_status"];

        $fraudStatus = $data["fraud_status"];
        $paymentType = $data["payment_type"];
        $realOrderId = explode("-", $orderId);


        $order = Order::find(intval($realOrderId[0]));
        if (!$order) {
            return \response()->json([
                "status" => "error",
                "message" => "Order not found."
            ], 404);
        }

        if ($order->status === "success") {
            return \response()->json([
                "status" => "error",
                "message" => "Operation not permited."
            ], 405);
        }


        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'challenge') {
                $order->status = "challenge";
            } else if ($fraudStatus == 'accept') {
                $order->status = "success";
            }
        } else if ($transactionStatus == 'settlement') {
            $order->status = "success";
        } else if (
            $transactionStatus == 'cancel' ||
            $transactionStatus == 'deny' ||
            $transactionStatus == 'expire'
        ) {
            $order->status = "failure";
        } else if ($transactionStatus == 'pending') {
            $order->status = "pending";
        }
        PaymentLog::create([
            "status" => $transactionStatus,
            "payment_type" => $paymentType,
            "order_id" => intval($realOrderId[0]),
            "raw_response" => json_encode($data)
        ]);

        $order->save();

        return \response()->json("ok");
    }
}
