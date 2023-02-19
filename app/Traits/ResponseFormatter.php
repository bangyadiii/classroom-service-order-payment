<?php

namespace App\Traits;

trait ResponseFormatter
{
    public function success($code = 200, $message = null, $data = null)
    {
        return response()->json([
            "meta" => [
                "success" => true,
                "code" => $code,
                "message" => $message
            ],
            "data" => $data
        ], $code);
    }
    public function error($code = 500, $message = null, $data = null)
    {
        return response()->json([
            "meta" => [
                "success" => false,
                "code" => $code,
                "message" => $message
            ],
            "errors" => $data
        ], $code);
    }
}
