<?php
namespace  FOM\UserBundle\Security\Authentication\Provider;

use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Provider\UserAuthenticationProvider;
use Symfony\Bridge\Monolog\Logger;
use Mapbender\Component\Ldap;
use FOM\UserBundle\Entity\User;

class LdapAuthenticationProvider extends UserAuthenticationProvider
{
    private $encoderFactory;
    private $userProvider;
    private $params;
    protected $logger;

    /**
     * @param Service $service
     * @param \Symfony\Component\Security\Core\User\UserProviderInterface $userProvider
     * @param UserCheckerInterface $userChecker
     * @param $providerKey
     * @param EncoderFactoryInterface $encoderFactory
     * @param bool $hideUserNotFoundExceptions
     */
    public function __construct($fom_params,
        UserProviderInterface $userProvider,
        UserCheckerInterface $userChecker,
        $providerKey,
        EncoderFactoryInterface $encoderFactory,
        $hideUserNotFoundExceptions = true,
        Logger $logger)
    {
        parent::__construct($userChecker, $providerKey, $hideUserNotFoundExceptions);
        $this->encoderFactory = $encoderFactory;
        $this->userProvider = $userProvider;
        $this->params = $fom_params['ldap'];
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        $currentUser = $token->getUser();

        if ($currentUser instanceof UserInterface) {
            if ($currentUser->getPassword() !== $user->getPassword()) {
                throw new BadCredentialsException('The credentials were changed from another session.');
            }
        } else {
            if (!$presentedPassword = $token->getCredentials()) {
                throw new BadCredentialsException('The presented password cannot be empty.');
            }

            if($user instanceof User) {
                $encoder = $this->encoderFactory->getEncoder($user);
                if(!$encoder->isPasswordValid($user->getPassword(), $presentedPassword, $user->getSalt())) {
                    throw new BadCredentialsException('The presented password is invalid.');
                }
            } else {
                $ldap = new Ldap($this->params['host'],
                    $this->params['port'],
                    $this->params['version']);
                $bind = $ldap->bind($user->getUsername(), $presentedPassword);
                $this->logger->debug(sprintf('LDAP bind with username "%s" and password "%s" yielded: %s',
                    $user->getUsername(), $presentedPassword, print_r($bind, true)));


                if (! $bind) {
                    throw new BadCredentialsException('The presented password is invalid.');
                }

                // There's likely more data in the LDAP result now after a successful bind
                $this->userProvider->refreshUser($user);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function retrieveUser($username, UsernamePasswordToken $token)
    {
        $user = $token->getUser();
        if ($user instanceof UserInterface) {
            return $user;
        }

        try {
            $user = $this->userProvider->loadUserByUsername($username);

            if (!$user instanceof UserInterface) {
                throw new AuthenticationServiceException('The user provider must return a UserInterface object.');
            }

            return $user;
        } catch (UsernameNotFoundException $notFound) {
            throw $notFound;
        } catch (\Exception $repositoryProblem) {
            throw new AuthenticationServiceException($repositoryProblem->getMessage(), $token, 0, $repositoryProblem);
        }
    }
}
