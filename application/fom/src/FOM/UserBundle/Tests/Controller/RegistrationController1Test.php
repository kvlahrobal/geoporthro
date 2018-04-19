<?php

namespace FOM\UserBundle\Controller;

use FOM\Component\Test\SharedApplicationWebTestCase;

class RegistrationController1Test extends SharedApplicationWebTestCase
{
    /**
     * Test that self registration is disabled with the default configuration.
     */
    public function testIndex() {
        $crawler = self::$client->request('GET', '/user/registration');
        $this->assertEquals(403, self::$client->getResponse()->getStatusCode());
    }
}
