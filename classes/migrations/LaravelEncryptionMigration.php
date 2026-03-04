<?php

namespace APP\plugins\generic\thoth\classes\migrations;

use Exception;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use PKP\config\Config;

class LaravelEncryptionMigration extends Migration
{
    private const ENCRYPTION_CIPHER = 'AES-256-CBC';
    private const BASE64_PREFIX = 'base64:';

    public function up(): void
    {
        DB::table('plugin_settings')
            ->where('plugin_name', 'thothplugin')
            ->where('setting_name', 'password')
            ->get(['context_id', 'setting_value'])
            ->each(function ($row) {
                if (empty($row->setting_value)) {
                    return;
                }

                try {
                    $decryptedPassword = $this->decryptString($row->setting_value);
                } catch (Exception $e) {
                    return;
                }

                $encryptedValue = Crypt::encrypt($decryptedPassword);

                DB::table('plugin_settings')
                    ->where('plugin_name', 'thothplugin')
                    ->where('context_id', $row->context_id)
                    ->where('setting_name', 'password')
                    ->update(['setting_value' => $encryptedValue]);
            });
    }

    private function decryptString(string $encryptedText): string
    {
        $secret = $this->getSecretFromConfig();
        $encrypter = new Encrypter($secret, self::ENCRYPTION_CIPHER);

        $encryptedText = str_replace(self::BASE64_PREFIX, '', $encryptedText);
        $payload = base64_decode($encryptedText);

        return $encrypter->decrypt($payload);
    }

    private function getSecretFromConfig(): string
    {
        $secret = Config::getVar('security', 'api_key_secret');
        if ($secret === '') {
            throw new Exception(
                "A secret must be set in the config file ('api_key_secret')"
                . ' so that keys can be encrypted and decrypted'
            );
        }

        return hash('sha256', $secret, true);
    }
}
