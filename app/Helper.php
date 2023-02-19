<?php

use Illuminate\Support\Facades\Http;


function error($code = 500, $message = "")
{
    return [
        "meta" => [
            "success" => false,
            "http_code" => $code,
            "message" => $message ?? "service user unavailable.",
        ],
        "data" => null
    ];
}

function createPremiumAccess($body)
{
    $url = env("SERVICE_COURSE_URL") . "/api/v1/mycourses/premium";

    try {
        $response = Http::acceptJson()->post($url, $body);
        $data = $response->json();
        $data["meta"]["http_code"] = $response->getStatusCode();
        return $data;
    } catch (\Throwable $th) {
        return error(500, "Service course unavailable");
    }
}
