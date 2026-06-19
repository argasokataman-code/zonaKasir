<?php

namespace App\Http\Controllers\Api\Tenants;

use App\Http\Controllers\Controller;
use App\Http\Resources\AboutResource;
use App\Models\Tenants\About;
use App\Models\Tenants\Setting;
use App\Services\Tenants\AboutService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AboutController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->buildResponse()
            ->setData(new AboutResource(About::select('id', 'shop_name', 'shop_location', 'business_type', 'photo')->first()))
            ->present();
    }

    public function update(Request $request, AboutService $aboutService): JsonResponse
    {
        $this->validate($request, [
            'shop_name' => ['nullable', 'string'],
            'shop_location' => ['nullable', 'string'],
            'business_type' => ['nullable', 'in:retail,wholesale,fnb,fashion,pharmacy,other'],
            'other_business_type' => ['required_if:business_type,other'],
            'owner_name' => ['nullable', 'string'],
            'uploaded_file_id' => ['nullable', 'integer', 'exists:uploaded_files,id'],
        ]);

        try {
            DB::beginTransaction();
            
            $aboutService->createOrUpdate($request->all());
            Setting::set('currency', $request->currency ?? 'IDR');
            
            DB::commit();

            return $this->buildResponse()
                ->setMessage('About updated successfully')
                ->present();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to update about: ' . $e->getMessage())
                ->present();
        }
    }
}