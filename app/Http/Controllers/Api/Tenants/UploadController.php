<?php

namespace App\Http\Controllers\Api\Tenants;

use App\Http\Controllers\Controller;
use App\Models\Tenants\UploadedFile;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'file' => config('upload.rules', ['required', 'file', 'mimes:jpeg,png,jpg,gif,pdf,doc,docx,xls,xlsx', 'max:5120']),
        ]);

        if ($request->file('file')->isValid()) {
            $name = Str::random(40) . '.' . $request->file('file')->extension();
            $tmpDisk = config('upload.tmp_disk', config('filesystems.tmp_disk'));

            Storage::disk($tmpDisk)->put($name, file_get_contents($request->file('file')->getRealPath()));

            $fullUrl = UploadedFile::urlFromPath($name, $tmpDisk);

            $uploadedFile = UploadedFile::create([
                'name' => $name,
                'original_name' => $originalName = $request->file('file')->getClientOriginalName(),
                'url' => $fullUrl,
                'mime_type' => $request->file('file')->getMimeType(),
                'extension' => $request->file('file')->extension(),
                'size' => $request->file('file')->getSize(),
                'relative_path' => $name,
                'path' => '',
                'disk' => $tmpDisk,
            ]);
        } else {
            return $this->buildResponse()
                ->setCode(400)
                ->setMessage('File is not valid')
                ->present();
        }

        return $this->buildResponse()
            ->setData([
                'id' => $uploadedFile->id,
                'name' => $name,
                'relative_path' => $name,
                'url' => $fullUrl,
                'original_name' => $originalName,
            ])
            ->setMessage('File uploaded successfully')
            ->present();
    }
}
