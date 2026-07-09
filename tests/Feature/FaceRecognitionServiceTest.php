<?php

namespace Tests\Feature;

use App\Support\FaceRecognitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FaceRecognitionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_embedding_from_file_returns_embedding(): void
    {
        Http::fake([
            'http://127.0.0.1:5000/api/face/embedding' => Http::response([
                'ok' => true,
                'embedding' => [0.1, 0.2, 0.3],
            ]),
        ]);

        $path = tempnam(sys_get_temp_dir(), 'face-');
        file_put_contents($path, 'fake-image');

        try {
            $embedding = app(FaceRecognitionService::class)->createEmbeddingFromFile($path);
        } finally {
            @unlink($path);
        }

        $this->assertSame([0.1, 0.2, 0.3], $embedding);
    }

    public function test_verify_returns_match_payload(): void
    {
        Http::fake([
            'http://127.0.0.1:5000/api/face/verify' => Http::response([
                'ok' => true,
                'matched' => true,
                'distance' => 0.31,
                'tolerance' => 0.5,
            ]),
        ]);

        $result = app(FaceRecognitionService::class)->verify([0.1, 0.2], 'data:image/jpeg;base64,ZmFrZQ==');

        $this->assertTrue($result['matched']);
        $this->assertSame(0.31, $result['distance']);
        $this->assertSame(0.5, $result['tolerance']);
    }

    public function test_service_error_becomes_validation_error(): void
    {
        Http::fake([
            'http://127.0.0.1:5000/api/face/verify' => Http::response([
                'ok' => false,
                'message' => 'Wajah tidak terdeteksi.',
            ], 400),
        ]);

        $this->expectException(ValidationException::class);

        app(FaceRecognitionService::class)->verify([0.1, 0.2], 'data:image/jpeg;base64,ZmFrZQ==');
    }
}
