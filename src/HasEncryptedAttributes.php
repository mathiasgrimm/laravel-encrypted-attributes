<?php

namespace MathiasGrimm\LaravelEncryptedAttributes;

use Exception;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/** @property array $encrypted */

trait HasEncryptedAttributes
{
    
    /**
     * @param $value
     * @return string
     */
    public function encryptAttribute($value)
    {
        $env = config('app.env');

        return "{$env}." . Crypt::encryptString($value);
    }

    /**
     * @param $value
     * @return string
     */
    public function decryptAttribute($value)
    {
        $env = $this->encryptedEnvironment($value);
        $value = str_replace("{$env}.", "", $value);

        return Crypt::decryptString($value);
    }
    
    public function encryptedEnvironment($value): string
    {
        $parts = explode('.', $value);
        if (count($parts) < 2) {
            throw new NoEnvironmentDefinedException("attribute does not have an environment defined");
        }
        return $parts[0];
    }

    /**
     * @param $key
     * @return mixed|string
     */
    public function getAttribute($key)
    {
        $environment = false;
        
        $possibleKey = null;
        if (substr($key, -10) == '_decrypted') {
            $possibleKey = substr($key, 0, -10);

            if (in_array($possibleKey, $this->encrypted ?? [])) {
                $key = $possibleKey;
            }
        }

        if (substr($key, -12) == '_environment') {
            $possibleKey = substr($key, 0, -12);

            if (in_array($possibleKey, $this->encrypted ?? [])) {
                $key = $possibleKey;
                $environment = true;
            }
        }

        $value = parent::getAttribute($key);
        
        if ($value && $possibleKey) {
            try {
                if ($environment) {
                    $value = $this->encryptedEnvironment($value);
                } else {
                    $value = $this->decryptAttribute($value);    
                }
            } catch (NoEnvironmentDefinedException $e) {
                throw new NoEnvironmentDefinedException("attribute {$key} does not have an environment defined");
            } catch (DecryptException $e) {
                throw new DecryptException("can't decrypt attribute {$key}");
            }
        }

        return $value;
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        if (substr($key, -4) == '_raw') {
            $possibleKey = substr($key, 0, -4);

            if (in_array($possibleKey, $this->encrypted ?? [])) {
                $key = $possibleKey;
            }
        } elseif ($value && in_array($key, $this->encrypted ?? [])) {
            $value = $this->encryptAttribute($value);
        }

        return parent::setAttribute($key, $value);
    }
}