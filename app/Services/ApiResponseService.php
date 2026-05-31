<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class ApiResponseService
{
    private $data;

    private $message;

    private $code = 200;

    private $pagination = null;

    public function setData($data): self
    {
        $this->data = $data;
        return $this;
    }

    public function setMessage($message): self
    {
        $this->message = $message;
        return $this;
    }

    public function setCode($code): self
    {
        $this->code = $code;
        return $this;
    }

    public function present(): JsonResponse
    {
        if ($this->data instanceof Paginator || isset($this->data->resource) && $this->data?->resource instanceof Paginator) {
            $this->setPaginator($this->data);
        } else if ($this->data instanceof LengthAwarePaginator || isset($this->data->resource) && $this->data?->resource instanceof LengthAwarePaginator) {
            $this->setLengthAwarePaginator($this->data);
        }

        $response = [
            'success' => true,
            'data' => $this->data,
            'message' => $this->message
        ];

        if ($this->pagination !== null) {
            $response['pagination'] = $this->pagination;
        }

        if ($this->code != 200) {
            $response['success'] = false;
        }

        if ($this->message == "") {
            unset($response['message']);
        }

        if (is_null($this->data)) {
            unset($response['data']);
        }

        return response()->json($response, $this->code);
    }

    private function setPaginator($data): self
    {
        $resource = isset($this->data->resource) ? $this->data->resource : $this->data;

        $this->pagination = [
            'current_page' => $resource->currentPage(),
            'per_page' => $resource->perPage(),
            'prev_page_url' => $resource->previousPageUrl(),
            'next_page_url' => $resource->nextPageUrl(),
            'from' => $resource->firstItem(),
            'to' => $resource->lastItem(),
            'path' => $resource->resolveCurrentPath(),
        ];
        $this->data = $resource->items();

        return $this;
    }

    private function setLengthAwarePaginator(): self
    {
        $resource = isset($this->data->resource) ? $this->data->resource : $this->data;

        $this->pagination = [
            'current_page' => $resource->currentPage(),
            'per_page' => $resource->perPage(),
            'last_page' => $resource->lastPage(),
            'total' => $resource->total(),
            'prev_page_url' => $resource->previousPageUrl(),
            'next_page_url' => $resource->nextPageUrl(),
            'from' => $resource->firstItem(),
            'to' => $resource->lastItem(),
            'path' => $resource->resolveCurrentPath(),
        ];
        $this->data = $resource->items();

        return $this;
    }
}
