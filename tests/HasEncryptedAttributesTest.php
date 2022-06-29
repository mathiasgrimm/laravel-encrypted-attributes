<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use MathiasGrimm\EncryptedAttributes\HasEncryptedAttributes;
use Orchestra\Testbench\TestCase;
use Throwable;

class HasEncryptedAttributesTest extends TestCase
{
    private function getModel()
    {
        return new class extends Model {
            use HasEncryptedAttributes;

            protected $encrypted = [
                'access_token',
            ];
        };
    }
    
    public function test_it_does_not_throw_exception_when_accessing_a_non_encrypted_attribute()
    {
        $model = $this->getModel();
        $this->assertNull($model->other_attribute);
    }

    public function test_it_does_not_throw_exception_when_accessing_a_non_encrypted_attribute_when_using_the_decrypted_suffix()
    {
        $model = $this->getModel();
        $this->assertNull($model->other_attribute_decrypted);
    }

    public function test_it_does_not_throw_exception_when_accessing_a_non_encrypted_attribute_when_using_the_environment_suffix()
    {
        $model = $this->getModel();
        $this->assertNull($model->other_attribute_environment);
    }

    public function test_it_does_not_throw_exception_when_accessing_a_non_encrypted_attribute_when_using_the_environment_raw()
    {
        $model = $this->getModel();
        $this->assertNull($model->other_attribute_raw);
        
        $model->other_attribute_raw = 10;
        $this->assertEquals(10, $model->other_attribute_raw);
        $this->assertNull($model->other_attribute_decrypted);
        $this->assertNull($model->other_attribute_environment);
    }

    public function test_it_sets_raw_value()
    {
        $model = $this->getModel();

        $encryptedString = 'testing' . Crypt::encryptString('plain-text-string');
        $model->access_token_raw = $encryptedString;
        $this->assertEquals($encryptedString, $model->access_token);
    }

    public function test_it_gets_encrypted_value_by_default()
    {
        $model = $this->getModel();

        $encryptedString = Crypt::encryptString('plain-text-string');

        $model->access_token_raw = "testing.{$encryptedString}";
        $this->assertEquals('testing.'.$encryptedString, $model->access_token);
    }

    public function test_it_decrypts_value_when_using_the_decrypted_suffix()
    {
        $model = $this->getModel();

        $model->access_token = 'plain-text-string';
        $this->assertEquals('plain-text-string', $model->access_token_decrypted);
    }

    public function test_it_decrypts_value_when_using_the_decrypted_suffix_and_setting_raw()
    {
        $model = $this->getModel();

        $model->access_token_raw = 'testing.' . Crypt::encryptString('plain-text-string');
        $this->assertEquals('plain-text-string', $model->access_token_decrypted);
    }
    
    public function test_it_gets_null_when_getting_using_the_raw_suffix()
    {
        $model = $this->getModel();

        $model->access_token_raw = 'testing.' . Crypt::encryptString('plain-text-string');
        $this->assertNull($model->access_token_raw);
    }
    
    public function test_it_gets_the_environment_when_using_the_environment_suffix()
    {
        $model = $this->getModel();

        $model->access_token_raw = 'testing.' . Crypt::encryptString('plain-text-string');
        $this->assertEquals('testing', $model->access_token_environment);

        $model->access_token_raw = 'local.' . Crypt::encryptString('plain-text-string');
        $this->assertEquals('local', $model->access_token_environment);

        $model->access_token_raw = 'production.' . Crypt::encryptString('plain-text-string');
        $this->assertEquals('production', $model->access_token_environment);

        $model->access_token_raw = 'staging.' . Crypt::encryptString('plain-text-string');
        $this->assertEquals('staging', $model->access_token_environment);
    }
    
    public function test_toArray_serializes_encrypted_value()
    {
        $model = $this->getModel();
        $encryptedString = 'testing.' . Crypt::encryptString('plain-text-string');
        $model->access_token_raw = $encryptedString;
        
        $this->assertEquals(['access_token' => $encryptedString], $model->toArray());
    }

    public function test_toJson_serializes_encrypted_value()
    {
        $model = $this->getModel();
        $encryptedString = 'testing.' . Crypt::encryptString('plain-text-string');
        $model->access_token_raw = $encryptedString;

        $this->assertEquals(json_encode(['access_token' => $encryptedString]), $model->toJson());
    }
    
    public function test_it_decrypts_value_from_a_different_environment()
    {
        $model = $this->getModel();
        $encryptedString = 'some-environment.' . Crypt::encryptString('plain-text-string');
        $model->access_token_raw = $encryptedString;
        $this->assertEquals('some-environment', $model->access_token_environment);
        $this->assertEquals('plain-text-string', $model->access_token_decrypted);
    }
    
    public function test_it_throws_exception_when_it_cant_decrypt()
    {
        $model = $this->getModel();
        $model->access_token_raw = 'testing.invalid-encrypted-string';
        try {
            $_ = $model->access_token_decrypted;
            $this->fail("should fail to decrypt value");
        } catch (Throwable $e) {
            $this->assertEquals("can't decrypt attribute access_token", $e->getMessage());
        }
    }

    public function test_it_throws_exception_when_it_doesnt_have_an_environment()
    {
        $model = $this->getModel();
        $model->access_token_raw = 'invalid-encrypted-string';
        try {
            $tmp = $model->access_token_decrypted;
            $this->fail("should fail to decrypt value");
        } catch (Throwable $e) {
            $this->assertEquals("attribute access_token does not have an environment defined", $e->getMessage());
        }
    }
}