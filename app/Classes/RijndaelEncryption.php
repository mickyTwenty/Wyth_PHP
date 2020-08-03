<?php
namespace App\Classes;

class RijndaelEncryption
{
    public static function encrypt($plaintext)
    {
        $key = constants('global.encryption.key');

        return bin2hex(openssl_encrypt($plaintext, 'AES-128-CBC', hex2bin($key), OPENSSL_RAW_DATA, hex2bin($key)));
    }

    public static function decrypt($cipher)
    {
        $key       = constants('global.encryption.key');
        $decrypted = openssl_decrypt(hex2bin($cipher), 'AES-128-CBC', hex2bin($key), OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, hex2bin($key));
        $padSize   = ord(substr($decrypted, -1));

        return substr($decrypted, 0, $padSize * -1);
    }
}
