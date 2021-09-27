<?php

namespace Laraneat\Modules\Traits;

use Illuminate\Http\JsonResponse;

trait ResponseHelpersTrait
{
    public function json($data, int $status = 200, array $headers = [], int $options = 0): JsonResponse
    {
        return new JsonResponse($data, $status, $headers, $options);
    }

    public function created($data = null, int $status = 201, array $headers = [], int $options = 0): JsonResponse
    {
        return new JsonResponse($data, $status, $headers, $options);
    }

    public function deleted($data = null, int $status = 204, array $headers = [], int $options = 0): JsonResponse
    {
        return new JsonResponse($data, $status, $headers, $options);
    }

    public function accepted($data = null, int $status = 202, array $headers = [], int $options = 0): JsonResponse
    {
        return new JsonResponse($data, $status, $headers, $options);
    }

    public function noContent(int $status = 204, array $headers = [], int $options = 0): JsonResponse
    {
        return new JsonResponse(null, $status, $headers, $options);
    }
}
