<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class FaceRecognitionService
{
    /**
     * @return array<int, float>
     */
    public function createEmbeddingFromFile(string $path): array
    {
        if (! is_file($path)) {
            throw ValidationException::withMessages([
                'face_photo_path' => 'File foto wajah tidak ditemukan.',
            ]);
        }

        try {
            $response = Http::timeout(30)
                ->attach('image', file_get_contents($path), basename($path))
                ->post($this->url('/api/face/embedding'));

            $payload = $response->throw()->json();
        } catch (ConnectionException|RequestException $exception) {
            throw ValidationException::withMessages([
                'face_photo_path' => $this->messageFromException($exception, 'Service wajah tidak dapat memproses foto.'),
            ]);
        }

        $embedding = $payload['embedding'] ?? null;

        if (! is_array($embedding) || $embedding === []) {
            throw ValidationException::withMessages([
                'face_photo_path' => 'Service wajah tidak mengembalikan embedding yang valid.',
            ]);
        }

        return array_map('floatval', $embedding);
    }

    /**
     * @param  array<int, float>  $referenceEmbedding
     * @return array{matched: bool, distance: float, tolerance: float}
     */
    public function verify(array $referenceEmbedding, string $image): array
    {
        try {
            $response = Http::timeout(30)->post($this->url('/api/face/verify'), [
                'reference_embedding' => array_values($referenceEmbedding),
                'image' => $image,
                'tolerance' => (float) config('attendance.face.tolerance', 0.5),
            ]);

            $payload = $response->throw()->json();
        } catch (ConnectionException|RequestException $exception) {
            throw ValidationException::withMessages([
                'face' => $this->messageFromException($exception, 'Service wajah tidak dapat memverifikasi wajah.'),
            ]);
        }

        if (! array_key_exists('matched', $payload) || ! isset($payload['distance'], $payload['tolerance'])) {
            throw ValidationException::withMessages([
                'face' => 'Service wajah mengembalikan response yang tidak valid.',
            ]);
        }

        return [
            'matched' => (bool) $payload['matched'],
            'distance' => (float) $payload['distance'],
            'tolerance' => (float) $payload['tolerance'],
        ];
    }

    protected function url(string $path): string
    {
        return rtrim((string) config('attendance.face.service_url'), '/').$path;
    }

    protected function messageFromException(ConnectionException|RequestException $exception, string $fallback): string
    {
        if ($exception instanceof RequestException) {
            $message = $exception->response->json('message');

            if (is_string($message) && filled($message)) {
                return $message;
            }
        }

        return $fallback;
    }
}
