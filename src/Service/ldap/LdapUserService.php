<?php


namespace App\Service\ldap;


use App\data\LdapType;
use App\Entity\LdapUserProperties;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\LdapException;
use Symfony\Component\Ldap\Ldap;

class LdapUserService
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * This function retrieves the user
     * @param Entry $entry
     * @param string $userNameAttribute
     * @param $mapper
     * @return User|object
     */
    public function retrieveUserfromDatabasefromUserNameAttribute(Entry $entry,LdapType $ldapType)
    {
        $uid = $entry->getAttribute($ldapType->getUserNameAttribute())[0];
        $email = $entry->getAttribute($ldapType->getMapper()['email'])[0];
        $firstName = $entry->getAttribute($ldapType->getMapper()['firstName'])[0];
        $lastName = $entry->getAttribute($ldapType->getMapper()['lastName'])[0];

        $user = $this->em->getRepository(User::class)->findOneBy(array('username' => $uid));

        if (!$user) {
            $user = new User();
            $user->setUsername($uid);
            $user->setCreatedAt(new \DateTime());
            $user->setUid(md5(uniqid()));
            $user->setUuid(md5(uniqid()));
            $ldap = new LdapUserProperties();
            $ldap->setLdapHost($ldapType->getUrl());
            $ldap->setLdapDn($entry->getDn());
            $ldap->setLdapNumber($ldapType->getSerVerId());
            $user->setLdapUserProperties($ldap);
            if ($ldapType->getRdn()) {
                $ldap->setRdn($ldapType->getRdn().'='.$entry->getAttribute($ldapType->getRdn())[0]);
            }
        }
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    /**
     * This function connects all users in the Database with the adressbook of all available other users.
     * So everyon can search for everyone in the whole jitsi-admin system
     *
     */
    public function connectUserwithAllUSersInAdressbock()
    {
        $allUSer = $this->em->getRepository(User::class)->findAll();
        foreach ($allUSer as $data) {
            foreach ($allUSer as $data2) {
                $data->addAddressbook($data2);
            }
            $this->em->persist($data);
        }
        $this->em->flush();
    }

    /**
     * returns all valid users from the database which are in the ldap and the Database
     * @param Ldap $ldap
     * @param $ldapServerId
     */
    public function syncDeletedUser(Ldap $ldap, LdapType $ldapType)
    {
        $user = $this->em->getRepository(User::class)->findUsersByLdapServerId($ldapType->getSerVerId());
        foreach ($user as $data) {
            $this->checkUserInLdap($data, $ldap);
        }
        $user = $this->em->getRepository(User::class)->findUsersByLdapServerId($ldapType->getSerVerId());
        return $user;
    }

    /**
     * Search for the USer in LDAP
     * @param User $user
     * @param Ldap $ldap
     */
    public function checkUserInLdap(User $user, Ldap $ldap): ?Entry
    {
        $object = null;
        try {
            $query = $ldap->query($user->getLdapUserProperties()->getLdapDn(), '(&(cn=*))');
            $object = $query->execute();
        } catch (LdapException $e) {
            $this->deleteUser($user);
            return null;
        }
        return $object->toArray()[0];
    }

    /**
     * Delete User and remove all Addressbooks entrys
     * @param User $user
     */
    public function deleteUser(User $user)
    {
        foreach ($user->getAddressbookInverse() as $u) {
            $u->removeAddressbook($user);
            $this->em->persist($u);
        }
        foreach ($user->getRooms() as $r) {
            $user->removeRoom($r);
        }
        foreach ($user->getRoomModerator() as $r) {
            $user->removeRoomModerator($r);
        }
        $this->em->persist($user);
        $this->em->flush();
        $this->em->remove($user);
        $this->em->flush();
    }
}