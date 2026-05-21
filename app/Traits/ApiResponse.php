<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Standardized API response formatting for professional REST APIs.
 */
trait ApiResponse
{
    protected function success(mixed $data = null, ?string $message = null, int $statusCode = 200, array $meta = []): JsonResponse
    {
        $response = ['success' => true];
        if ($message !== null) $response['message'] = $message;
        if ($data !== null) $response['data'] = $this->morphToArray($data);
        if (!empty($meta)) $response['meta'] = $meta;
        return response()->json($response, $statusCode);
    }

    protected function paginated(LengthAwarePaginator $paginator, ?string $message = null, int $statusCode = 200): JsonResponse
    {
        $response = ['success' => true];
        if ($message !== null) $response['message'] = $message;
        $response['data'] = $paginator->items();
        $response['meta'] = ['pagination' => [
            'total' => $paginator->total(),
            'count' => $paginator->count(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'total_pages' => $paginator->lastPage(),
            'has_more' => $paginator->hasMorePages(),
        ]];
        return response()->json($response, $statusCode);
    }

    protected function error(string $message, int $statusCode = 400, ?string $errorCode = null, array $errors = [], mixed $data = null): JsonResponse
    {
        $response = ['success' => false, 'message' => $message];
        if ($errorCode !== null) $response['error_code'] = $errorCode;
        if (!empty($errors)) $response['errors'] = $errors;
        if ($data !== null) $response['data'] = $this->morphToArray($data);
        return response()->json($response, $statusCode);
    }

    protected function validationError(array $errors, ?string $message = null): JsonResponse
    {
        return $this->error($message ?? 'The given data was invalid.', 422, 'VALIDATION_ERROR', $errors);
    }

    protected function notFound(?string $message = null): JsonResponse
    {
        return $this->error($message ?? 'Resource not found.', 404, 'NOT_FOUND');
    }

    protected function unauthorized(?string $message = null): JsonResponse
    {
        return $this->error($message ?? 'Unauthorized.', 401, 'UNAUTHORIZED');
    }

    protected function forbidden(?string $message = null): JsonResponse
    {
        return $this->error($message ?? 'Forbidden.', 403, 'FORBIDDEN');
    }

    protected function serverError(string $message, ?string $errorCode = null): JsonResponse
    {
        return $this->error($message, 500, $errorCode ?? 'INTERNAL_SERVER_ERROR');
    }

    private function morphToArray(mixed $data): mixed
    {
        if ($data instanceof JsonResource || $data instanceof ResourceCollection) return $data->resolve();
        if ($data instanceof \Illuminate\Database\Eloquent\Model) return $data->toArray();
        if ($data instanceof \Illuminate\Database\Eloquent\Collection) return $data->toArray();
        return $data;
    }
}
