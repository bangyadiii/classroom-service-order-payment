<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
            throw new BadRequestHttpException("Signaturekey not valid");
        }

        $transactionStatus = $data["transaction_status"];

        $fraudStatus = $data["fraud_status"];
        $paymentType = $data["payment_type"];
        $realOrderId = explode("-", $orderId);


        $order = Order::find(intval($realOrderId[0]));
        if (!$order) {
            \abort(404, "Order not found.");
        }

        if ($order->status === "success") {
            throw new BadRequestHttpException("Operation not permited.");
        }


        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'challenge') {
                $order->status = "challenge";
            } elseif ($fraudStatus == 'accept') {
                $order->status = "success";
            }
        } elseif ($transactionStatus == 'settlement') {
            $order->status = "success";
        } elseif (
            $transactionStatus == 'cancel' ||
            $transactionStatus == 'deny' ||
            $transactionStatus == 'expire'
        ) {
            $order->status = "failure";
        } elseif ($transactionStatus == 'pending') {
            $order->status = "pending";
        }
        PaymentLog::create([
            "status" => $transactionStatus,
            "payment_type" => $paymentType,
            "order_id" => intval($realOrderId[0]),
            "raw_response" => json_encode($data)
        ]);

        $order->save();

        $respons = \createPremiumAccess([
            'course_id' => $order['course_id'],
            'user_id' => $order['user_id']
        ]);

        return \response()->json($respons, $respons["meta"]["http_code"]);
    }
}
