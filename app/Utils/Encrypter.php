<?php namespace app\Utils;

class Encrypter {

    protected $key;

    public function __construct($key) {
        $this->key = $key;
        
    }

    public function decrypt($payload)
    {
        $payload = $this->getJsonPayload($payload);

        $iv = base64_decode($payload['iv']);

        $decrypted = openssl_decrypt($payload['value'], 'AES-256-CBC', $this->key, 0, $iv);

        if ($decrypted === false) {
            throw new Exception('Could not decrypt the data.');
        }

        return unserialize($decrypted);
    }



    protected function getJsonPayload($payload)
    {
        $payload = json_decode(base64_decode($payload), true);
        
        // If the payload is not valid JSON or does not have the proper keys set we will
        // assume it is invalid and bail out of the routine since we will not be able
        // to decrypt the given value. We'll also check the MAC for this encryption.
        if ( ! $payload || $this->invalidPayload($payload))
        {
            throw new Exception('Invalid data.');
        }

        /*if ( ! $this->validMac($payload))
        {
            throw new Exception('MAC is invalid.');
        }*/

        return $payload;
    }

    protected function invalidPayload($data)
    {
        return ! is_array($data) || ! isset($data['iv']) || ! isset($data['value']) || ! isset($data['mac']);
    }


}