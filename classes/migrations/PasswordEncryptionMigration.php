<?php

namespace APP\plugins\generic\thoth\classes\migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use APP\plugins\generic\thoth\classes\encryption\DataEncryption;

class PasswordEncryptionMigration extends Migration
{
    public function up(): void
    {
        $encrypter = new DataEncryption();
        if (!$encrypter->secretConfigExists()) {
            return;
        }

        DB::table('plugin_settings')
            ->where('plugin_name', 'thothplugin')
            ->where('setting_name', 'password')
            ->get(['context_id', 'setting_value'])
            ->each(function ($row) use ($encrypter) {
                if (empty($row->setting_value) || $encrypter->textIsEncrypted($row->setting_value)) {
                    return;
                }

                if ($this->isJWT($row->setting_value)) {
                    $decodedPayload = $this->decodeJWT($row->setting_value);
                    if ($decodedPayload !== null) {
                        $row->setting_value = json_decode($decodedPayload);
                    }
                }

                $encryptedValue = $encrypter->encryptString($row->setting_value);
                DB::table('plugin_settings')
                    ->where('plugin_name', 'thothplugin')
                    ->where('context_id', $row->context_id)
                    ->where('setting_name', 'password')
                    ->update(['setting_value' => $encryptedValue]);
            });
    }

    public function base64URLDecode($data)
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        $data = strtr($data, '-_', '+/');
        return base64_decode($data);
    }

    public function isJWT(string $token): bool
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        foreach ($parts as $part) {
            if (!preg_match('/^[A-Za-z0-9\-_]+$/', $part)) {
                return false;
            }
        }

        [$header, $payload, $signature] = $parts;
        if ($this->base64URLDecode($header) === false || $this->base64URLDecode($payload) === false) {
            return false;
        }

        return true;
    }

    public function decodeJWT($string): ?string
    {
        list($header, $payload, $signature) = explode('.', $string);
        $decodedPayload = $this->base64URLDecode($payload);
        return $decodedPayload ? $decodedPayload : null;
    }
}
