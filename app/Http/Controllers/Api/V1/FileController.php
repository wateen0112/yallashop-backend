<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function __construct(
        protected CloudinaryService $cloudinary
    ) {}

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:51200|mimes:jpg,jpeg,png,gif,webp,mp4,webm,ogg',
            'folder' => 'nullable|string|max:100',
        ]);

        $file = $request->file('file');
        $folder = $request->input('folder', 'general');

        $result = $this->cloudinary->upload($file, $folder);

        return ApiResponse::success([
            'url'        => $result['url'],
            'public_id'  => $result['public_id'],
            'media_type' => $result['media_type'],
            'mime_type'  => $result['mime_type'],
            'size'       => $result['size'],
            'width'      => $result['width'],
            'height'     => $result['height'],
        ], 'File uploaded successfully', 201);
    }
}
