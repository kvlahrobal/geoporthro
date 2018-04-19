<?php

namespace ArsGeografica\Signing;

use JMS\Serializer\SerializerBuilder;


/**
 * Object signer.
 *
 * This class can generate a signature for any JSON-serializable object.
 *
 * @author Christian Wygoda
 */
class Signer
{
    protected $key;
    protected $salt;
    protected $sep;

    protected $serializer;

    /**
     * Create a signer with a key
     *
     * @param  string  $key   Signing key to use
     */
    public function __construct($key, $sep=':', $salt=null)
    {
        $this->key = $key;
        $this->salt = (null !== $salt ? $salt : 'ArsGeograficaSigningSigner');
        $this->sep = $sep;
    }

    /**
     * Create a signed string (including the input value)
     *
     * @param   string  $string     String to be signed
     * @return  string  $signature
     */
    public function sign($value)
    {
        return sprintf('%s%s%s', $value, $this->sep, $this->signature($value));
    }

    /**
     * Test value signature. Raised BadSignatureException o fail.
     *
     * @param   string  $signed_value  Signature to check
     * @return  string  $value         Unsigned original value
     */
    public function unsign($signed_value)
    {
        if(false === strstr($signed_value, $this->sep)) {
            throw new BadSignatureException('No "' . $this->sep . '" in signed value.');
        }

        list($signature, $value) = array_map('strrev', explode($this->sep, strrev($signed_value), 2));

        if($this->signature($value) === $signature) {

            return $value;
        }

        throw new BadSignatureException('Signature "' . $signature . '" does not match value "' . $value . '"');
    }

    /**
     * Sign a serializable object
     *
     * @param   object      Data object to sign
     * @param   Serializer  Serializer to use, defaults to JSONSerializer with GetSetMethodNormalizer
     * @param   boolean     Compress data, defaults to false
     * @return  string      Object signature
     */
    public function dump($object, Serializer $serializer = null, $encoder = 'json', $compress = false)
    {
        $s = ($serializer ? $serializer : $this->getSerializer());
        $data = $s->serialize($object, $encoder);

        $isCompressed = false;

        if($compress) {
            $compressed = gzcompress($data, 9);
            if(strlen($compressed) < (strlen($data) -1 )) {
                $data = $compressed;
                $isCompressed = true;
            }
        }

        $base64d = base64_encode($data);
        if($isCompressed) {
            $base64d = '.' . $base64d;
        }

        return $this->sign($base64d);
    }

    public function load($signature, $class, Serializer $serializer = null, $encoder = 'json')
    {
        $base64d = $this->unsign($signature);

        $decompress = false;
        if($base64d[0] == '.') {
            $base64d = substr($base64d, 1);
            $decompress = true;
        }

        $data = base64_decode($base64d);

        if($decompress) {
            $data = gzuncompress($data);
        }

        $s = $serializer ? $serializer : $this->getSerializer();
        return $s->deserialize($data, $class, $encoder);
    }

    /**
     * Create a string signature (not including the input value)
     *
     * @param   string  $value      String to be signed
     * @return  string  $signature
     */
    public function signature($value)
    {
        $salt = $this->salt . 'signer';
        $key = sha1($salt . $this->key, true);

        $saltedHmac = hash_hmac('sha1', $value, $key, true);

        $base64 = base64_encode($saltedHmac);
        $base64UrlSafe = str_replace(array('+', '/'), array('-', '_'), $base64);
        return rtrim($base64UrlSafe, '=');
    }

    protected function getSerializer()
    {
        if(!$this->serializer) {
            $this->serializer = SerializerBuilder::create()->build();
        }
        return $this->serializer;
    }
}
