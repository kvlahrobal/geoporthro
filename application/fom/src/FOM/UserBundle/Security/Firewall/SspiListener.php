<?php

namespace FOM\UserBundle\Security\Firewall;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use FOM\UserBundle\Security\Authentication\Token\SspiUserToken;

class SspiListener implements ListenerInterface {

    public function __construct(SecurityContextInterface $context, AuthenticationManagerInterface $manager) {
        $this->context = $context;
        $this->manager = $manager;
    }

    public function handle(GetResponseEvent $evt) {
        $request = $evt->getRequest();

        // check if username is set, let it override
        if($request->get('_username')) {
            return;
        }

        // check if another token exists, then skip
        if($this->context->getToken() && (!$this->context->getToken() instanceof SspiUserToken)) {
            return;
        }

        $server = $request->server;

        $remote_user = $server->get('REMOTE_USER');

        if(!$remote_user) {
            return;
        }

        $cred = explode('\\', $remote_user);
        if (count($cred) == 1) {
            array_unshift($cred, "unknown");
        }

        $token = new SspiUserToken();
        $token->setUser($cred[1]);

        try {
            $token = $this->manager->authenticate($token);
            $this->context->setToken($token);
            return;
        } catch(AuthenticationException $failed) {
            $this->context->setToken(null);
            return;
        }
    }

}
