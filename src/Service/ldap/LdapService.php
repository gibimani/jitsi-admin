<?php


namespace App\Service\ldap;



use App\data\LdapType;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\InvalidCredentialsException;
use Symfony\Component\Ldap\Exception\LdapException;
use Symfony\Component\Ldap\Exception\NotBoundException;
use Symfony\Component\Ldap\Ldap;

class LdapService
{

    private $ldapUserService;
    private $em;

    public function __construct(LdapUserService $ldapUserService, EntityManagerInterface $entityManager)
    {
        $this->ldapUserService = $ldapUserService;
        $this->em = $entityManager;

    }

    /**
     * This function creates a ldap connection
     * @param $url
     * @param $login
     * @param $password
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return Ldap|null
     */
    public function createLDAP($url, $login, $password, $anonym = false): ?Ldap
    {
        try {
            $tmp = Ldap::create('ext_ldap', ['connection_string' => $url]);
            if($anonym === false){
                $tmp->bind($login, $password);
            }else{
                $tmp->bind();
            }
            return $tmp;
        } catch (\Exception $e) {
            throw $e;
        }

    }


    /**
     * this function queries for users in the ldap
     * @param Ldap $ldap
     * @param string $userDn
     * @param string $objectclass
     * @param string $scope
     * @return \Symfony\Component\Ldap\Entry[]
     */
    public function retrieveUser(Ldap $ldap, string $userDn, string $objectclass, string $scope)
    {

        $options = array(
            'scope' => $scope
        );
        $query = $ldap->query($userDn, $this->buildObjectClass($objectclass), $options);
        $user = $query->execute();
        return $user->toArray();
    }

    /**
     * @param $objectClassString
     * @return string
     */
    public function buildObjectClass($objectClassString): string
    {
        $objectclass = '(&(|';
        foreach (explode(',', $objectClassString) as $data2) {
            $objectclass .= '(objectclass=' . $data2 . ')';
        }
        $objectclass .= '))';
        return $objectclass;
    }


    /**
     * @param Ldap $ldap
     * @param $userDn
     * @param $objectClasses
     * @param $scope
     * @param $mapper
     * @param $url
     * @param $usernameAttribute
     * @param OutputInterface $output
     * @param InputInterface $input
     * @return Entry[]|null
     */
    public function fetchLdap(LdapType $ldap){

        $user = null;
        try {
            $userLdap =
                $this->retrieveUser(
                    $ldap->getLdap(),
                    $ldap->getUserDn(),
                    $ldap->getObjectClass(),
                    $ldap->getScope()
                );
            foreach ($userLdap as $u) {
                $user[] = $this->ldapUserService->retrieveUserfromDatabasefromUserNameAttribute($u, $ldap);
                  }
        } catch (\Exception $e) {
            throw $e;
        }
        $this->ldapUserService->syncDeletedUser($ldap->getLdap(),$ldap);
        return array('ldap'=>$ldap,'user'=>$user);
    }
}