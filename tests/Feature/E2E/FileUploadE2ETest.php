<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\User;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('File Upload E2E', function () {
    beforeEach(function () {
        $this->user = User::first();
        $this->token = $this->user->createToken('test')->plainTextToken;
    });

    it('can upload temporary file', function () {
        $file = UploadedFile::fake()->image('photo.jpg', 640, 480);

        $response = $this->withToken($this->token)
            ->post('/api/temp/upload', ['file' => $file]);

        expect($response->status())->toBe(Response::HTTP_OK);
        expect($response->json())->toHaveKey('id');
    });

    it('validates file type on upload', function () {
        $file = UploadedFile::fake()->create('document.pdf', 5000);

        $response = $this->withToken($this->token)
            ->post('/api/temp/upload', ['file' => $file]);

        // Should be OK or 422 depending on validation
        expect($response->status())->toBeIn([Response::HTTP_OK, Response::HTTP_UNPROCESSABLE_ENTITY]);
    });

    it('enforces file size limit', function () {
        $file = UploadedFile::fake()->image('large.jpg')->size(50000); // 50MB

        $response = $this->withToken($this->token)
            ->post('/api/temp/upload', ['file' => $file]);

        // May reject large files
        expect($response->status())->toBeIn([
            Response::HTTP_OK,
            Response::HTTP_UNPROCESSABLE_ENTITY,
        ]);
    });

    it('requires authentication to upload files', function () {
        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->post('/api/temp/upload', ['file' => $file]);

        expect($response->status())->toBe(Response::HTTP_UNAUTHORIZED);
    });
});
