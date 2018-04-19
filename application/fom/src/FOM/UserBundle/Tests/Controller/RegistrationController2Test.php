<?php

namespace FOM\UserBundle\Controller;

use FOM\Component\Test\SharedApplicationWebTestCase;

class RegistrationController2Test extends SharedApplicationWebTestCase
{
    protected static $username = 'testuser';
    protected static $password = 'testpassword';
    protected static $email = 'testuser@example.com';

    // Using a different config environment. Due to PHP's crazy late static
    // binding for properties, we have to redeclare $application and $client
    // as well... Oh well...
    protected static $application;
    protected static $client;
    protected static $options = array(
        'environment' => 'test_selfregister');

    /**
     * Test a full self registration workflow
     */
    public function testSelfRegistration() {
        $client = static::$client;

        // Get registration form
        $crawler = $client->request('GET', '/user/registration');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Send form
        $form = $crawler->filter('form button')->form(array(
            'User[username]' => self::$username,
            'User[password][first]' => self::$password,
            'User[password][second]' => self::$password,
            'User[email]' => self::$email,
        ));
        $client->submit($form);
        $this->assertContains($client->getResponse()->getStatusCode(), array(301, 302));

        // Check E-Mail
        $mailCollector = $client->getProfile()->getCollector('swiftmailer');
        $this->assertEquals(1, $mailCollector->getMessageCount());

        $collectedMessages = $mailCollector->getMessages();
        $message = $collectedMessages[0];

        // Asserting e-mail data
        $this->assertInstanceOf('Swift_Message', $message);
        $this->assertEquals(self::$email, key($message->getTo()));
        $this->assertEquals(1, preg_match('/\/user\/activate\?token=(\w+)/',
            $message->getBody(), $matches));
        $token = $matches[1];
        // @todo: Go on with token

        // Try to login without activating, must redirect back to login form
        $crawler = $client->request('GET', '/user/login');
        $form = $crawler->filter('form input[type="submit"]')->form(array(
            '_username' => self::$username,
            '_password' => self::$password));
        $client->submit($form);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $client->getResponse());
        $this->assertRegExp('/\/user\/login$/', $client->getResponse()->headers->get('location'));

        // Activate using token
        $crawler = $client->request('GET', '/user/activate?token=' . $token);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $client->getResponse());
        $this->assertRegExp('/\/user\/registration\/done$/', $client->getResponse()->headers->get('location'));

        // Login
        $crawler = $client->request('GET', '/user/login');
        $form = $crawler->filter('form input[type="submit"]')->form(array(
            '_username' => self::$username,
            '_password' => self::$password));
        $client->submit($form);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $client->getResponse());
        $securityCollector = $client->getProfile()->getCollector('security');
        $this->assertEquals(true, $securityCollector->isAuthenticated());
        $this->assertEquals(self::$username, $securityCollector->getUser());
    }
}
