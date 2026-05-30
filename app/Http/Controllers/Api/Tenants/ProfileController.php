<?php

namespace App\Http\Controllers\Api\Tenants;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Models\Tenants\UploadedFile;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->buildResponse()
            ->setData(new ProfileResource(auth()->user()))
            ->present();
    }

    public function update(Request $request): JsonResponse
    {
        $phoneRules = config('validation.phone');
        $locale = app()->getLocale();
        $phoneConfig = $phoneRules[$locale] ?? $phoneRules['default'];
        
        $this->validate($request, [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . auth()->id()],
            'phone' => ['nullable', 'string', "digits_between:{$phoneConfig['min']},{$phoneConfig['max']}"],
            'address' => ['nullable', 'string', 'max:500'],
            'locale' => ['nullable', 'in:id,en,es'],
            'timezone' => ['nullable', 'timezone'],
            'uploaded_file_id' => ['nullable', 'integer', 'exists:uploaded_files,id'],
        ]);

        try {
            DB::beginTransaction();
            /** @var \App\Models\Tenants\User $user */
            $user = auth()->user();
            $user->update($request->only('name', 'email'));

            /** @var \App\Models\Tenants\Profile $profile */
            $profile = $user->profile;
            $profile = $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $request->only('phone', 'address', 'locale', 'timezone')
            );

            if ($request->filled('uploaded_file_id')) {
                $tmpFile = UploadedFile::find($request->uploaded_file_id);

                if ($tmpFile && $tmpFile->relative_path !== $profile->photo) {
                    $relativePath = $tmpFile->moveToPublic('profile', $profile->photo ?: null);
                    $profile->update([
                        'photo' => $relativePath,
                    ]);
                }
            }

            DB::commit();
            
            // Reload user with fresh profile data
            $user->refresh();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->buildResponse()
                ->setCode($e->getCode() !== 0 ? $e->getCode() : 500)
                ->setMessage($e->getMessage())
                ->present();
        }

        return $this->buildResponse()
            ->setData(new ProfileResource($user))
            ->setMessage('Profile updated successfully')
            ->present();
    }
}