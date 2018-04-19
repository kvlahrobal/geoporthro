<?php

namespace ArsGeografica\Signing\Tests;

use Symfony\Component\Yaml\Parser;
use ArsGeografica\Signing\Signer;
use ArsGeografica\Signing\Tests\TestClass;


class SigningTest extends \PHPUnit_Framework_TestCase
{
    protected $secretKey;
    protected $data;

    protected function setUp()
    {
        $fixture = dirname(__FILE__) . '/testdata.yml';
        $yaml = new Parser();

        $data = $yaml->parse(file_get_contents($fixture));
        $this->secretKey = $data['secretKey'];
        $this->data = $data['data'];
    }

    public function testSign()
    {
        $signer = new Signer($this->secretKey);

        foreach($this->data as $idx => $data) {
            if(array_key_exists('sign', $data) && null !== $data['sign']) {
                $this->assertEquals($data['sign'], $signer->sign($data['in']));
            }
        }
    }

    /**
     * @expectedException \ArsGeografica\Signing\BadSignatureException
     */
    public function testUnsignFailSeparator()
    {
        $signer = new Signer($this->secretKey);
        $signer->unsign('abcd');
    }

    /**
     * @expectedException \ArsGeografica\Signing\BadSignatureException
     */
    public function testUnsignFailSignature()
    {
        $signer = new Signer($this->secretKey);
        foreach($this->data as $idx => $data) {
            if(array_key_exists('sign', $data) && null !== $data['sign']) {
                $signer->unsign('x' . $signer->sign($data['in']));
            }
        }
    }

    public function testUnsign()
    {
        $signer = new Signer($this->secretKey);

        foreach($this->data as $idx => $data) {
            if(array_key_exists('sign', $data) && null !== $data['sign']) {
                $signed = $signer->sign($data['in']);
                $this->assertEquals($data['in'], $signer->unsign($signed));
            }
        }
    }

    public function testObjectSigning()
    {
        $signer = new Signer($this->secretKey);
        // Load the Type annotation class into memory. Use is not enough...
        new \JMS\Serializer\Annotation\Type();

        $obj = new TestClass();
        $obj->setWhen(new \DateTime())
            ->setData(array('1', 2, 'three'));

        $signature = $signer->dump($obj, null, 'json', true);

        $this->AssertTrue($obj->equals($signer->load($signature, 'ArsGeografica\Signing\Tests\TestClass')));
    }

    public function testUrlSafety()
    {
        $signer = new Signer($this->secretKey);

        $value = 'http://example.com/wms/foo/bar';
        $signature = $signer->signature($value);

        $this->AssertTrue(false == strpos($signature, '+'));
        $this->AssertTrue(false == strpos($signature, '_'));
    }
}
