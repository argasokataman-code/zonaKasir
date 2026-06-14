<?php

namespace App\Services\Tenants;

use App\Models\Tenants\About;
use App\Models\Tenants\UploadedFile;
use App\Models\Tenants\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class AboutService
{
    public function createOrUpdate(array $data): void
    {
        \Illuminate\Support\Facades\Log::info('AboutService::createOrUpdate', [
            'has_photo_key' => array_key_exists('photo', $data),
            'photo_raw' => $data['photo'] ?? 'NOT_SET',
            'has_uploaded_file_id' => array_key_exists('uploaded_file_id', $data),
            'uploaded_file_id' => $data['uploaded_file_id'] ?? 'NOT_SET',
        ]);

        $about = About::query()
            ->updateOrCreate([
                'id' => About::first()?->getKey() ?? null,
            ], Arr::only($data, [
                'shop_name',
                'shop_location',
                'business_type',
                'other_business_type',
                'bank_name',
                'bank_account_name',
                'bank_account_number',
                'bank_code',
                'photo',
            ]));

        $owner = User::owner()->first();
        if ($owner && isset($data['owner_name'])) {
            $owner->name = $data['owner_name'];
            $owner->save();
        }

        if (array_key_exists('uploaded_file_id', $data)) {
            $tmpFile = UploadedFile::find($data['uploaded_file_id']);

            \Illuminate\Support\Facades\Log::info('AboutService: uploaded_file_id handling', [
                'uploaded_file_id' => $data['uploaded_file_id'],
                'tmpFile_found' => $tmpFile ? 'yes' : 'no',
                'about_photo_before' => $about->photo,
                'tmpFile_relative_path' => $tmpFile?->relative_path,
            ]);

            if ($tmpFile && $tmpFile->relative_path !== $about->photo) {
                try {
                    $relativePath = $tmpFile->moveToPublic('profile', $about->photo ?: null);
                    $about->update([
                        'photo' => $relativePath,
                    ]);

                    \Illuminate\Support\Facades\Log::info('AboutService: photo updated', [
                        'relativePath' => $relativePath,
                        'about_photo_after' => $about->fresh()->photo,
                    ]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('AboutService: moveToPublic failed', [
                        'error' => $e->getMessage(),
                    ]);
                }
            } elseif (! $tmpFile && $about->photo) {
                $this->deletePhoto($about);
            }
        }

        if (array_key_exists('uploaded_file_id', $data) && $data['uploaded_file_id'] === null && $about->photo) {
            $this->deletePhoto($about);
        }
    }

    private function deletePhoto(About $about): void
    {
        $uploadedFile = UploadedFile::where('relative_path', $about->photo)->first()
            ?? UploadedFile::where('url', $about->photo)->first();

        if ($uploadedFile) {
            $uploadedFile->deleteFromPublic('profile');
        } else {
            $uploadDisk = config('filesystems.upload_disk');
            if (Storage::disk($uploadDisk)->exists($about->photo)) {
                Storage::disk($uploadDisk)->delete($about->photo);
            }
        }

        $about->update([
            'photo' => null,
        ]);
    }
}
