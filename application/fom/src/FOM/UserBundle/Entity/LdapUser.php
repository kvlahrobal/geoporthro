<?php

/**
 * TODO: Validation
 * TODO: Basic user data
 * TODO: User profiles
 */

namespace FOM\UserBundle\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use IMAG\LdapBundle\User\LdapUser as IMAGLdapUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User entity.
 *
 * This needs enhancement, email should probably required. And we need a way
 * to implements user profiles which can vary from installation to
 * installation.
 *
 * @author Christian Wygoda
 * @author apour
 * @author Paul Schmidt
 * @author Arne Schubert
 *
 * @UniqueEntity("username")
 * @UniqueEntity("email")
 */
class LdapUser extends IMAGLdapUser implements AdvancedUserInterface {
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=false, length=255, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(min=3)
     */
    protected $username;

    /**
     * @ORM\Column(type="string", nullable=false, length=255, unique=true)
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    protected $email;

    /**
     * @ORM\Column
     * @Assert\NotBlank()
     * @Assert\Length(min=8)
     */
    protected $password;

    /**
     * @ORM\Column
     */
    protected $salt;

    /**
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $registrationTime;

    /**
     *
     * @ORM\Column(type="string", nullable=true, length=50)
     */
    protected $registrationToken;

    /**
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $resetTime;

    /**
     *
     * @ORM\Column(type="string", nullable=true, length=50)
     */
    protected $resetToken;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $loginFailed;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $loginFailCount;

    /**
     * @ORM\ManyToMany(targetEntity="Group", inversedBy="users")
     * @ORM\JoinTable(name="fom_ldap_users_groups")
     */
    protected $groups;

    /**
     * The profile is not stored here, but a placeholder is needed
     */
    protected $profile;

    public function __construct() {
        $this->groups = new ArrayCollection();
    }

    /**
     * Set id
     *
     * @param integer $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return $this
     */
    public function setUsername($username) {
        $this->username = $username;
        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return $this
     */
    public function setEmail($email) {
        $this->email = $email;
        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return $this
     */
    public function setPassword($password) {
        $this->password = $password;
        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * Set salt
     *
     * @param string $salt
     */
    public function setSalt($salt) {
        $this->salt = $salt;
    }

    /**
     * Get salt
     *
     * @param string
     * @return null|string
     */
    public function getSalt() {
        return $this->salt;
    }

    /**
     * Set registrationTime
     *
     * @param text $registrationTime
     */
    public function setRegistrationTime($registrationTime)
    {
        $this->registrationTime = $registrationTime;
    }

    /**
     * Get registrationTime
     *
     * @return text
     */
    public function getRegistrationTime()
    {
        return $this->registrationTime;
    }

    /**
     * Set registrationToken
     *
     * @param text $registrationToken
     */
    public function setRegistrationToken($registrationToken)
    {
        $this->registrationToken = $registrationToken;
    }

    /**
     * Get registrationToken
     *
     * @return string
     */
    public function getRegistrationToken()
    {
        return $this->registrationToken;
    }

    /**
     * Set resetTime
     *
     * @param text $resetTime
     */
    public function setResetTime($resetTime)
    {
        $this->resetTime = $resetTime;
    }

    /**
     * Get resetTime
     *
     * @return text
     */
    public function getResetTime()
    {
        return $this->resetTime;
    }

    /**
     * Set resetToken
     *
     * @param text $resetToken
     */
    public function setResetToken($resetToken)
    {
        $this->resetToken = $resetToken;
    }

    /**
     * Get resetToken
     *
     * @return string
     */
    public function getResetToken()
    {
        return $this->resetToken;
    }

    /**
     * Add groups
     *
     * @param Group $group
     * @return $this
     */
    public function addGroups(Group $group) {
        $this->groups[] = $group;
        return $this;
    }

    /**
     * Get groups
     *
     * @return ArrayCollection
     */
    public function getGroups() {
        return $this->groups;
    }

    /**
     * Get role objects
     *
     * @return array
     */
    public function getRoles() {
        $roles = array();
        foreach($this->groups as $group) {
            $roles[] = $group->getAsRole();
        }
        $roles[] = 'ROLE_USER';
        return $roles;
    }

    /**
     * Erase sensitive data like plain password. Don't fiddle with persisted data in here!
     */
    public function eraseCredentials() {
    }

    /**
     * Compare users
     *
     * This user class is only compatible with itself and compares the
     * username property. If you'r needs differ, use a subclass.
     *
     * @param UserInterface $user The user to compare
     * @return bool
     */
    public function equals(UserInterface $user) {
        return (get_class() === get_class($user)
            && $this->getUsername() === $user->getUsername());
    }

    public function isAccountNonExpired()
    {
        if($this->profile && method_exists($this->profile, 'isAccountNonExpired')) {
            return $this->profile->isAccountNonExpired();
        }
        return true;
    }

    /**
     * Upon failed logins, the account will be locked for a certain time.
     * The lock duration grows exponentially (2s^(n-1)) to divert brute
     * force attacks while still allowing to log in if the password was
     * forgotten, but remembered later.
     *
     * @return boolean
     */
    public function isAccountNonLocked()
    {
        $unLocked = true;
        if(($this->getLoginFailed() && $this->getLoginFailCount())) {
            if($this->getLoginFailCount() >= 13) {
                // Lock for at max one hour
                $lockSeconds = 3600;
            } else {
                $lockSeconds = pow(2, $this->getLoginFailCount() - 1);
            }

            $lockInterval = new \DateInterval('PT' . $lockSeconds . 'S');
            $lockEnd = clone $this->getLoginFailed();
            $lockEnd->add($lockInterval);

            $now = new \DateTime();
            $unLocked = $now > $lockEnd;
        }

        return $unLocked;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        if($this->profile && method_exists($this->profile, 'isEnabled')) {
            return $this->profile->isEnabled();
        }
        return $this->registrationToken === null;
    }

    /**
     * Checks whether the user is an admin for the given type (or is superadmin
     * if type is omitted)
     *
     * @param string $type Type of admin to check
     * @return bool
     */
    public function isAdmin($type = null)
    {
        if($this->getId() === 1) {
            return true;
        }

        //$role = ($type !== null ? 'ROLE_ADMIN_' .$type : 'ROLE_ADMIN');

        return false;
    }

    public function setProfile($profile)
    {
        $this->profile = $profile;
        return $this;
    }

    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * Set loginFailed
     *
     * @param \DateTime $loginFailed
     * @return User
     */
    public function setLoginFailed($loginFailed)
    {
        $this->loginFailed = $loginFailed;

        return $this;
    }

    /**
     * Get loginFailed
     *
     * @return \DateTime
     */
    public function getLoginFailed()
    {
        return $this->loginFailed;
    }

    /**
     * Set loginFailCount
     *
     * @param integer $loginFailCount
     * @return User
     */
    public function setLoginFailCount($loginFailCount)
    {
        $this->loginFailCount = $loginFailCount;

        return $this;
    }

    /**
     * Get loginFailCount
     *
     * @return integer
     */
    public function getLoginFailCount()
    {
        return $this->loginFailCount;
    }

    /**
     * Add groups
     *
     * @param \FOM\UserBundle\Entity\Group $groups
     * @return User
     */
    public function addGroup(\FOM\UserBundle\Entity\Group $groups)
    {
        $this->groups[] = $groups;

        return $this;
    }

    /**
     * Remove groups
     *
     * @param \FOM\UserBundle\Entity\Group $groups
     */
    public function removeGroup(\FOM\UserBundle\Entity\Group $groups)
    {
        $this->groups->removeElement($groups);
    }
    public function isEqualTo(UserInterface $user){
        if($this->username == $user->getUsername()){
            return true;
        }
        return false;
    }
}
