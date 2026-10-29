<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;

class CloudinaryService
{
    protected string $cloudName;
    protected string $apiKey;
    protected string $apiSecret;
    protected string $baseUrl;
    protected Client $http;

    public function __construct()
    {
        $this->cloudName = config('services.cloudinary.cloud_name');
        $this->apiKey = config('services.cloudinary.api_key');
        $this->apiSecret = config('services.cloudinary.api_secret');
        $this->baseUrl = "https://api.cloudinary.com/v1_1/{$this->cloudName}";

        $this->http = new Client([
            'verify' => false,
            'timeout' => 60,
            'auth' => [$this->apiKey, $this->apiSecret],
        ]);
    }

    public function upload(UploadedFile $file, string $folder = 'general'): array
    {
        $mediaType = str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image';

        $response = $this->http->post("{$this->baseUrl}/upload", [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($file->getRealPath(), 'r'),
                    'filename' => $file->getClientOriginalName(),
                ],
                ['name' => 'folder', 'contents' => "yallashop/{$folder}"],
                ['name' => 'resource_type', 'contents' => $mediaType],
                ['name' => 'public_id', 'contents' => uniqid()],
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        return [
            'url'        => $result['secure_url'],
            'public_id'  => $result['public_id'],
            'media_type' => $mediaType,
            'mime_type'   => $file->getMimeType(),
            'size'       => $file->getSize(),
            'width'      => $result['width'] ?? null,
            'height'     => $result['height'] ?? null,
        ];
    }

    public function delete(string $publicId): bool
    {
        try {
            $this->http->post("{$this->baseUrl}/destroy", [
                'form_params' => [
                    'public_id' => $publicId,
                ],
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
