<?php

use Illuminate\Support\Facades\Http;

function createPremiumAccess($body)
{
    $url = env("SERVICE_COURSE_URL") . "/api/v1/mycourses/premium";

    try {
        $response = Http::acceptJson()->post($url, $body);
        $data = $response->json();
        $data["http_code"] = $response->getStatusCode();
        return $data;
    } catch (\Throwable $th) {
        return response()->json([
            "status" => "error",
            "http_code" => 500,
            "message" => "Service course unavailable.",
        ]);
    }
}
