<?php declare(strict_types=1);

namespace Tests\Unit\Services\ML;

use Tests\TestCase;
use App\Services\ML\FraudMLModelEncryption;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class FraudMLModelEncryptionTest extends TestCase
{
    use RefreshDatabase;

    private FraudMLModelEncryption $encryption;
    private string $testModelPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set required environment variables
        putenv('FRAUDML_ENCRYPTION_KEY=' . str_repeat('a', 32));
        putenv('FRAUDML_SIGNATURE_KEY=' . str_repeat('b', 32));
        
        $this->encryption = app(FraudMLModelEncryption::class);
        $this->testModelPath = storage_path('models/fraud/test-model.joblib');
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $files = [
            $this->testModelPath,
            $this->testModelPath . '.enc',
            $this->testModelPath . '.sig',
        ];
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        parent::tearDown();
    }

    public function test_encrypt_model_creates_encrypted_file(): void
    {
        // Create test model file
        $modelDir = dirname($this->testModelPath);
        if (!is_dir($modelDir)) {
            mkdir($modelDir, 0755, true);
        }
        
        $modelContent = json_encode(['version' => 'test', 'weights' => [1, 2, 3]]);
        file_put_contents($this->testModelPath, $modelContent);

        $result = $this->encryption->encryptModel($this->testModelPath);

        $this->assertArrayHasKey('encrypted_path', $result);
        $this->assertArrayHasKey('signature_path', $result);
        $this->assertArrayHasKey('file_hash', $result);
        $this->assertArrayHasKey('is_encrypted', $result);
        $this->assertTrue($result['is_encrypted']);
        $this->assertFileExists($result['encrypted_path']);
        $this->assertFileExists($result['signature_path']);
        $this->assertFileDoesNotExist($this->testModelPath); // Original should be deleted
    }

    public function test_encrypt_model_generates_signature(): void
    {
        $modelDir = dirname($this->testModelPath);
        if (!is_dir($modelDir)) {
            mkdir($modelDir, 0755, true);
        }
        
        file_put_contents($this->testModelPath, json_encode(['test' => 'data']));

        $result = $this->encryption->encryptModel($this->testModelPath);

        $signature = file_get_contents($result['signature_path']);
        $this->assertNotEmpty($signature);
        $this->assertIsString($signature);
    }

    public function test_decrypt_model_with_valid_signature(): void
    {
        $modelDir = dirname($this->testModelPath);
        if (!is_dir($modelDir)) {
            mkdir($modelDir, 0755, true);
        }
        
        $originalContent = json_encode(['version' => 'test', 'weights' => [1, 2, 3]]);
        file_put_contents($this->testModelPath, $originalContent);

        $encryptionResult = $this->encryption->encryptModel($this->testModelPath);

        $decrypted = $this->encryption->decryptModel(
            $encryptionResult['encrypted_path'],
            $encryptionResult['signature_path']
        );

        $this->assertEquals($originalContent, $decrypted);
    }

    public function test_decrypt_model_fails_with_invalid_signature(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('signature verification failed');

        $modelDir = dirname($this->testModelPath);
        if (!is_dir($modelDir)) {
            mkdir($modelDir, 0755, true);
        }
        
        file_put_contents($this->testModelPath, json_encode(['test' => 'data']));

        $encryptionResult = $this->encryption->encryptModel($this->testModelPath);

        // Tamper with the encrypted file
        file_put_contents(
            $encryptionResult['encrypted_path'],
            'tampered content'
        );

        $this->encryption->decryptModel(
            $encryptionResult['encrypted_path'],
            $encryptionResult['signature_path']
        );
    }

    public function test_verify_model_integrity_with_valid_hash(): void
    {
        $modelDir = dirname($this->testModelPath);
        if (!is_dir($modelDir)) {
            mkdir($modelDir, 0755, true);
        }
        
        file_put_contents($this->testModelPath, json_encode(['test' => 'data']));

        $result = $this->encryption->encryptModel($this->testModelPath);

        $isValid = $this->encryption->verifyModelIntegrity(
            $result['encrypted_path'],
            $result['signature_path'],
            $result['file_hash']
        );

        $this->assertTrue($isValid);
    }

    public function test_verify_model_integrity_fails_with_invalid_hash(): void
    {
        $modelDir = dirname($this->testModelPath);
        if (!is_dir($modelDir)) {
            mkdir($modelDir, 0755, true);
        }
        
        file_put_contents($this->testModelPath, json_encode(['test' => 'data']));

        $result = $this->encryption->encryptModel($this->testModelPath);

        $isValid = $this->encryption->verifyModelIntegrity(
            $result['encrypted_path'],
            $result['signature_path'],
            'invalid_hash_123'
        );

        $this->assertFalse($isValid);
    }

    public function test_encrypt_model_throws_on_nonexistent_file(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Model file not found');

        $this->encryption->encryptModel('nonexistent/path/model.joblib');
    }

    public function test_decrypt_model_throws_on_nonexistent_files(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->encryption->decryptModel(
            'nonexistent/path/model.enc',
            'nonexistent/path/model.sig'
        );
    }

    public function test_generate_keys_returns_valid_keys(): void
    {
        $keys = FraudMLModelEncryption::generateKeys();

        $this->assertArrayHasKey('encryption_key', $keys);
        $this->assertArrayHasKey('signature_key', $keys);
        $this->assertIsString($keys['encryption_key']);
        $this->assertIsString($keys['signature_key']);
        $this->assertGreaterThan(32, strlen($keys['encryption_key'])); // hex encoded
        $this->assertGreaterThan(32, strlen($keys['signature_key'])); // hex encoded
    }

    public function test_encryption_without_env_keys_throws(): void
    {
        putenv('FRAUDML_ENCRYPTION_KEY=');
        putenv('FRAUDML_SIGNATURE_KEY=');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Encryption key not set');

        $modelDir = dirname($this->testModelPath);
        if (!is_dir($modelDir)) {
            mkdir($modelDir, 0755, true);
        }
        
        file_put_contents($this->testModelPath, json_encode(['test' => 'data']));

        $this->encryption->encryptModel($this->testModelPath);
    }

    public function test_encryption_is_deterministic_with_same_key(): void
    {
        $modelDir = dirname($this->testModelPath);
        if (!is_dir($modelDir)) {
            mkdir($modelDir, 0755, true);
        }
        
        $originalContent = json_encode(['test' => 'data']);
        file_put_contents($this->testModelPath, $originalContent);

        $result1 = $this->encryption->encryptModel($this->testModelPath);
        
        // Restore original file for second encryption
        file_put_contents($this->testModelPath, $originalContent);
        
        $result2 = $this->encryption->encryptModel($this->testModelPath);

        // Signatures should be the same with same key
        $this->assertEquals(
            file_get_contents($result1['signature_path']),
            file_get_contents($result2['signature_path'])
        );
    }
}
