# API Response Format Standard

## Overview

All API responses in the Lakasir POS system follow a standardized JSON format to ensure consistency and predictability for API consumers.

## Response Format

### Success Response (Status 2xx)
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Product Name",
    ...
  },
  "message": "Operation completed successfully",
  "code": 200
}
```

### Error Response (Status 4xx, 5xx)
```json
{
  "success": false,
  "data": null,
  "message": "Error description",
  "code": 400
}
```

### Validation Error Response (Status 422)
```json
{
  "success": false,
  "data": {
    "field_name": ["Error message for field"],
    "another_field": ["Error message 1", "Error message 2"]
  },
  "message": "Validation failed",
  "code": 422
}
```

## HTTP Status Codes

| Code | Meaning | Example |
|------|---------|---------|
| 200 | OK - Success | Product retrieved successfully |
| 201 | Created | New product created |
| 204 | No Content | Resource deleted (empty response) |
| 400 | Bad Request | Invalid parameters |
| 401 | Unauthorized | Missing/invalid authentication |
| 403 | Forbidden | Authenticated but lacks permission |
| 404 | Not Found | Resource doesn't exist |
| 409 | Conflict | Duplicate record or operation conflict |
| 422 | Unprocessable Entity | Validation error |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Server Error | Unexpected error |

## Building Responses

### Using ApiResponseService (Recommended)

All controllers should use the centralized `ApiResponseService` to build responses:

```php
use App\Services\ApiResponseService;

class ProductController extends Controller
{
    public function store(ProductRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $product = Product::create($request->validated());
            
            DB::commit();
            
            return $this->buildResponse()
                ->setCode(201)
                ->setData(new ProductResource($product))
                ->setMessage('Product created successfully')
                ->present();
        } catch (Exception $e) {
            DB::rollBack();
            
            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to create product: ' . $e->getMessage())
                ->present();
        }
    }
}
```

### Response Methods

The `ApiResponseService` provides a fluent interface:

```php
$this->buildResponse()
    ->setCode(200)              // HTTP status code (default 200)
    ->setData($data)            // Response payload (default null)
    ->setMessage('message')     // Human-readable message
    ->present()                 // Return JsonResponse
```

All methods return `$this` (except `present()`) for method chaining.

## Common Patterns

### Successful Data Retrieval
```php
return $this->buildResponse()
    ->setData(new UserResource($user))
    ->setMessage('User retrieved successfully')
    ->present();
```

### Resource Created
```php
return $this->buildResponse()
    ->setCode(201)
    ->setData(new ProductResource($product))
    ->setMessage('Product created successfully')
    ->present();
```

### Resource Deleted
```php
$user->delete();
return response()->json([
    'success' => true,
    'message' => 'User deleted successfully'
], 204);
```

### Validation Error
```php
// Automatically formatted by Laravel's built-in validation error handling
$request->validate([
    'email' => 'required|email|unique:users',
    'name' => 'required|string|max:255'
]);
```

### Not Found
```php
if (! $product) {
    return $this->buildResponse()
        ->setCode(404)
        ->setMessage('Product not found')
        ->present();
}
```

### Unauthorized
```php
return $this->buildResponse()
    ->setCode(401)
    ->setMessage('Unauthenticated')
    ->present();
```

### Forbidden
```php
if (! auth()->user()->can('edit-products')) {
    return $this->buildResponse()
        ->setCode(403)
        ->setMessage('You do not have permission to perform this action')
        ->present();
}
```

## Error Handling Strategy

1. **Validation Errors**: Return 422 with field-specific error messages
2. **Business Logic Errors**: Return 400-409 with descriptive message
3. **Authentication Errors**: Return 401 Unauthorized
4. **Authorization Errors**: Return 403 Forbidden
5. **Resource Not Found**: Return 404 Not Found
6. **Server Errors**: Return 500 with generic message (log details server-side)

## Rate Limiting Response

When rate limit is exceeded:
```json
{
  "success": false,
  "data": null,
  "message": "Too many requests. Please try again later",
  "code": 429
}
```

Response headers include:
- `RateLimit-Limit`: Total requests allowed
- `RateLimit-Remaining`: Requests remaining
- `RateLimit-Reset`: Unix timestamp when limit resets

## Idempotency

Critical operations support idempotency via `Idempotency-Key` header:

```
POST /api/transaction/selling
Idempotency-Key: 550e8400-e29b-41d4-a716-446655440000

Response-Headers:
Idempotency-Replayed: false  // true if result from cache
```

If the same key is sent within 24 hours, the cached response is returned.
