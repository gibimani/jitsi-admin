<?php

namespace App\Tests;

use App\dataType\LdapType;
use App\Service\ldap\LdapService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LdapConnectionTest extends KernelTestCase
{
    public static $UserInLDAP = 4;
    public static $UserInSubLDAP = 2;
    public static $UserInOneLDAP = 2;
    public $LDAPURL = 'ldap://192.168.230.128:10389';
    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $para = self::getContainer()->get(ParameterBagInterface::class);

        $this->LDAPURL = $para->get('ldap_test_url');
    }

    public function testConnectionOhneLogin(): void
    {

        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldap = $ldapConnection->createLDAP($this->LDAPURL, '', '', true);
        $this->assertEquals(self::$UserInLDAP, $ldap->query('o=unitTest,dc=example,dc=com', '(&(|(objectclass=person)(objectclass=organizationalPerson)(objectclass=user)))', array('scope' => 'sub'))->execute()->count());
    }

    public function testConnectionMitLogin(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldap = $ldapConnection->createLDAP($this->LDAPURL, 'uid=admin,ou=system', 'password');
        $this->assertEquals(self::$UserInLDAP, $ldap->query('o=unitTest,dc=example,dc=com', '(&(|(objectclass=person)(objectclass=organizationalPerson)(objectclass=user)))', array('scope' => 'sub'))->execute()->count());
    }

    public function testConnectionMitLoginOne(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldap = $ldapConnection->createLDAP($this->LDAPURL, 'uid=admin,ou=system', 'password');
        $this->assertEquals(self::$UserInOneLDAP, $ldap->query('o=unitTest,dc=example,dc=com', '(&(|(objectclass=person)(objectclass=organizationalPerson)(objectclass=user)))', array('scope' => 'one'))->execute()->count());
    }

    public function testcreateObjectClass(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldap = $ldapConnection->createLDAP($this->LDAPURL, 'uid=admin,ou=system', 'password');

        $this->assertEquals('(&(|(objectclass=person)(objectclass=organizationalPerson)(objectclass=user)))', $ldapConnection->buildObjectClass('person,organizationalPerson,user'));

    }

    public function testcreateFetchUserOne(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldap = $ldapConnection->createLDAP($this->LDAPURL, 'uid=admin,ou=system', 'password');
        $ldapType = new LdapType($ldapConnection);
        $ldapType->setUrl($this->LDAPURL);
        $ldapType->setSerVerId('Server1');
        $ldapType->setPassword('password');
        $ldapType->setScope('one');
        $ldapType->setMapper(array("firstName" => "givenName", "lastName" => "sn", "email" => "uid"));
        $ldapType->setSpecialFields(array("ou" => "ou", "departmentNumber" => "departmentNumber"));
        $ldapType->setUserDn('o=unitTest,dc=example,dc=com');
        $ldapType->setBindType('none');
        $ldapType->setRdn('uid');
        $ldapType->setLdap($ldap);
        $ldapType->setObjectClass('person,organizationalPerson,user');
        $ldapType->setUserNameAttribute('uid');
        $this->assertEquals(2, sizeof($ldapConnection->fetchLdap($ldapType)['user']));

    }

    public function testcreateFetchUserSub(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldap = $ldapConnection->createLDAP($this->LDAPURL, 'uid=admin,ou=system', 'password');
        $ldapType = new LdapType($ldapConnection);
        $ldapType->setUrl($this->LDAPURL);
        $ldapType->setSerVerId('Server1');
        $ldapType->setPassword('password');
        $ldapType->setScope('sub');
        $ldapType->setMapper(array("firstName" => "givenName", "lastName" => "sn", "email" => "uid"));
        $ldapType->setSpecialFields(array("ou" => "ou", "departmentNumber" => "departmentNumber"));
        $ldapType->setUserDn('o=unitTest,dc=example,dc=com');
        $ldapType->setBindType('none');
        $ldapType->setRdn('uid');
        $ldapType->setLdap($ldap);
        $ldapType->setObjectClass('person,organizationalPerson,user');
        $ldapType->setUserNameAttribute('uid');
        $this->assertEquals(self::$UserInLDAP, sizeof($ldapConnection->fetchLdap($ldapType)['user']));
    }

    public function testRetrieveUserOne(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldap = $ldapConnection->createLDAP($this->LDAPURL, 'uid=admin,ou=system', 'password');
        $this->assertEquals(self::$UserInOneLDAP, sizeof($ldapConnection->retrieveUser(
            $ldap,
            'o=unitTest,dc=example,dc=com',
            'person,organizationalPerson,user',
            'one'
        )));
    }
    public function testRetrieveUserSub(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldap = $ldapConnection->createLDAP($this->LDAPURL, 'uid=admin,ou=system', 'password');
        $this->assertEquals(self::$UserInLDAP, sizeof($ldapConnection->retrieveUser(
            $ldap,
            'o=unitTest,dc=example,dc=com',
            'person,organizationalPerson,user',
            'sub'
        )));
    }
}
