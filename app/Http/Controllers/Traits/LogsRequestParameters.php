<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Trait для логирования всех GET параметров запроса в точках входа API
 */
trait LogsRequestParameters
{
    /**
     * Логирует все параметры запроса (GET, POST, query, body)
     * 
     * @param Request $request
     * @param string $endpoint Имя endpoint для идентификации в логах
     * @return void
     */
    protected function logRequestParameters(Request $request, string $endpoint = ''): void
    {
        $allQueryParams = $request->query->all();
        $allPostParams = $request->post();
        $allRequestParams = $request->all();
        $queryString = $request->getQueryString();
        $requestBody = $request->getContent();
        $method = $request->method();
        $fullUrl = $request->fullUrl();
        $uri = $request->getRequestUri();
        
        Log::info("API request received: {$endpoint}", [
            'endpoint' => $endpoint ?: ($uri ?: 'unknown'),
            'method' => $method,
            'full_url' => $fullUrl,
            'request_uri' => $uri,
            'query_string' => $queryString,
            'all_query_params' => $allQueryParams,
            'query_params_count' => count($allQueryParams),
            'query_params_keys' => array_keys($allQueryParams),
            'all_post_params' => $allPostParams,
            'post_params_count' => count($allPostParams),
            'post_params_keys' => array_keys($allPostParams),
            'all_request_params' => $allRequestParams,
            'request_params_count' => count($allRequestParams),
            'request_params_keys' => array_keys($allRequestParams),
            'request_body' => $requestBody ? substr($requestBody, 0, 1000) : null, // Ограничиваем длину body для логов
            'request_body_length' => strlen($requestBody),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => [
                'content-type' => $request->header('Content-Type'),
                'accept' => $request->header('Accept'),
                'authorization' => $request->header('Authorization') ? 'present' : 'missing'
            ],
            'has_query_params' => !empty($allQueryParams),
            'has_post_params' => !empty($allPostParams),
            'has_body' => !empty($requestBody)
        ]);
    }
}

