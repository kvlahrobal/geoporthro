# String signing for PHP

PHP String signing component modeled after django.core.signing.

This component allows you to sign strings and check their signatures later on. Usefull for passing strings around and
checking them later.

## Usage

Create a signer object:

    $signer = new \ArsGeografica\Signing\Signer('MyPrivateKey');

You may pass a separator (single character, defaults to :) and a salt if you wish.

Sign a string:

    $signedValue = $signer->sign('Hello World');

This yields the signed string "Hello World:DqBSurOWfmzwg/yb6GRfWfDvV44" (value, separator, signature) which you can
check with

    $signer->unsign($signedValue);

If value and signature match, then the value is returned, otherwise a ArsGeografica\Signing\BadSignatureException will
be thrown.

___


[![Build Status](https://travis-ci.org/arsgeografica/signing.png?branch=master)](https://travis-ci.org/arsgeografica/signing) - master branch

[![Build Status](https://travis-ci.org/arsgeografica/signing.png?branch=develop)](https://travis-ci.org/arsgeografica/signing) - development branch