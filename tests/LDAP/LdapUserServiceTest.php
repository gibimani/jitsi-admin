<?php

namespace App\Tests\LDAP;

use App\dataType\LdapType;
use App\Entity\LobbyWaitungUser;
use App\Entity\Notification;
use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\Waitinglist;
use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use App\Service\IndexUserService;
use App\Service\ldap\LdapService;
use App\Service\ldap\LdapUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LdapUserServiceTest extends WebTestCase
{
    public $LDAPURL = 'ldap://192.168.230.128:10389';

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub

    }

    public function testRetrieveUserfromDatabasefromUserNameAttribute(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();
        $this->getParam();
        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldapUserService = $container->get(LdapUserService::class);
        $indexer = $container->get(IndexUserService::class);
        $em = self::getContainer()->get(EntityManagerInterface::class);
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
        $ldapType->setFilter('(&(mail=*))');
        $ldapType->setLdap($ldap);
        $ldapType->setObjectClass('person,organizationalPerson,user');
        $ldapType->setUserNameAttribute('uid');

        $ldapType->setScope('sub');
        $ldapType->setFilter(null);
        $ldapType->createLDAP();


        $entry = $ldapConnection->retrieveUser($ldapType);
        $this->assertEquals(LdapConnectionTest::$UserInLDAP+1, sizeof($entry));
        $ldapType->setScope('sub');
        $ldapType->setFilter('');
        $ldapType->createLDAP();

        $entry = $ldapConnection->retrieveUser($ldapType);
        $this->assertEquals(LdapConnectionTest::$UserInLDAP+1, sizeof($entry));
        $ldapType->setScope('sub');
        $ldapType->setFilter('(&(mail=*))');
        $ldapType->createLDAP();
        $entry = $ldapConnection->retrieveUser($ldapType);
        $users = array();
        foreach ($entry as $data) {
            $users[] = $ldapUserService->retrieveUserfromDatabasefromUserNameAttribute($data, $ldapType);
        }
        $this->assertEquals(LdapConnectionTest::$UserInLDAP, sizeof($users));
        $allUSers = $ldapUserService->connectUserwithAllUSersInAdressbock();
        foreach ($allUSers as $data) {
            $this->assertEquals(sizeof($allUSers), sizeof($data->getAddressbook()));
        }
        foreach ($allUSers as $data) {
            foreach ($allUSers as $data2) {
                $data->addAddressbook($data2);
                $em->persist($data);
            }
        }
        $em->flush();
        foreach ($allUSers as $data) {
            $this->assertEquals(sizeof($allUSers), sizeof($data->getAddressbook()));
        }
        $allUSers = $ldapUserService->connectUserwithAllUSersInAdressbock();
        foreach ($allUSers as $data) {
            $this->assertEquals(sizeof($allUSers), sizeof($data->getAddressbook()));
        }
        $allUSers = $ldapUserService->cleanUpAdressbook();
        foreach ($allUSers as $data) {
            $this->assertEquals(sizeof($allUSers) - 1, sizeof($data->getAddressbook()));
        }
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(array('username' => 'UnitTest1'));
        $this->assertNotEquals(null, $ldapUserService->checkUserInLdap($user, $ldapType));
        $user->getLdapUserProperties()->setLdapDn('uid=unitTest100,o=unitTest,dc=example,dc=com');
        $this->assertEquals(null, $ldapUserService->checkUserInLdap($user, $ldapType));

        $ldapUserService->deleteUser($user);
        $allUSerNew = $userRepository->findUsersfromLdapService();
        $allUSers = $ldapUserService->connectUserwithAllUSersInAdressbock();
        foreach ($allUSerNew as $data) {
            $this->assertEquals(sizeof($allUSerNew), sizeof($data->getAddressbook()));
        }
        foreach ($allUSerNew as $data){
            self::assertEquals($indexer->indexUser($data),$data->getIndexer());
        }

        foreach ($allUSerNew as $data) {
            if ($data->getUsername() === 'unitTestnoSF') {
                $this->assertEquals('', $data->getSpezialProperties()['ou']);
                $this->assertEquals('', $data->getSpezialProperties()['departmentNumber']);
            } else {
                $this->assertEquals('AA', $data->getSpezialProperties()['ou']);
                $this->assertEquals('45689', $data->getSpezialProperties()['departmentNumber']);
            }

        }
    }

    public function testRoomShowAttribute(): void
    {
        // (1) boot the Symfony kernel
        $client = self::createClient();
        $this->getParam();
        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldapUserService = $container->get(LdapUserService::class);
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
        $ldapType->setFilter('(&(mail=*))');
        $ldapType->setLdap($ldap);
        $ldapType->setObjectClass('person,organizationalPerson,user');
        $ldapType->setUserNameAttribute('uid');
        $ldapType->createLDAP();
        $entry = $ldapConnection->retrieveUser($ldapType);
        $users = array();
        foreach ($entry as $data) {
            $users[] = $ldapUserService->retrieveUserfromDatabasefromUserNameAttribute($data, $ldapType);
        }
        $this->assertEquals(LdapConnectionTest::$UserInLDAP, sizeof($users));
        $allUSers = $ldapUserService->connectUserwithAllUSersInAdressbock();
        foreach ($allUSers as $data) {
            $this->assertEquals(sizeof($allUSers), sizeof($data->getAddressbook()));
        }
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->flush();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $serverRepository = static::getContainer()->get(ServerRepository::class);
        $server = $serverRepository->findAll()[0];

        $user = $userRepository->findOneBy(array('username' => 'UnitTest1'));
        $user->addServer($server);
        $em->persist($user);
        $em->flush();
        $client->loginUser($user);
        $room = new Rooms();
        $room->setModerator($user);
        $room->addUser($user);
        $room->setStart(new \DateTime());
        $room->setEnddate((new \DateTime())->modify('+60min'));
        $room->setDuration(60);
        $room->setName('testRaum');
        $room->setServer($server);
        $room->setAgenda('Ich bin eine Testagenda');
        $room->setUid('testUid123');
        $room->setPublic(true);
        $room->setSequence(0);
        $em->persist($room);
        $em->flush();
        $crawler = $client->request('GET', '/room/dashboard');
        $this->assertResponseIsSuccessful();

        $this->assertEquals(
            1,
            $crawler->filter('.h5-responsive:contains("testRaum")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('p:contains("Geplant von: AA, 45689, Maus, Maike")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('.badge:contains("Organisator")')->count()
        );
    }

    public function testremoveUserFromLdap(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();
        $this->getParam();
        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldapUserService = $container->get(LdapUserService::class);
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
        $ldapType->setFilter('(&(mail=*))');
        $ldapType->setObjectClass('person,organizationalPerson,user');
        $ldapType->setUserNameAttribute('uid');
        $ldapType->createLDAP();
        $entry = $ldapConnection->retrieveUser($ldapType);
        $users = array();
        foreach ($entry as $data) {
            $users[] = $ldapUserService->retrieveUserfromDatabasefromUserNameAttribute($data, $ldapType);
        }
        $userRepository = static::getContainer()->get(UserRepository::class);
        $users = $userRepository->findUsersfromLdapService();
        $this->assertEquals(LdapConnectionTest::$UserInLDAP, sizeof($users));
        $user = $userRepository->findOneBy(array('username' => 'unitTest1'));
        $user->getLdapUserProperties()->setLdapDn('uid=unitTest100,o=unitTest,dc=example,dc=com');
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($user);
        $em->flush();
        $ldapUserService->syncDeletedUser($ldapType);
        $users = $userRepository->findUsersfromLdapService();
        $this->assertEquals(LdapConnectionTest::$UserInLDAP - 1, sizeof($users));
    }
    public function testrUsernoinFilterAnymore(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();
        $this->getParam();
        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldapUserService = $container->get(LdapUserService::class);
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
        $ldapType->setFilter(null);
        $ldapType->setObjectClass('person,organizationalPerson,user');
        $ldapType->setUserNameAttribute('uid');
        $ldapType->createLDAP();
        $entry = $ldapConnection->fetchLdap($ldapType);

        $userRepository = static::getContainer()->get(UserRepository::class);
        $users = $userRepository->findUsersfromLdapService();
        $this->assertEquals(LdapConnectionTest::$UserInLDAP+1, sizeof($users));
        $ldapType->setFilter('(&(mail=*))');
        $entry = $ldapConnection->fetchLdap($ldapType);

        $userRepository = static::getContainer()->get(UserRepository::class);
        $users = $userRepository->findUsersfromLdapService();
        $this->assertEquals(LdapConnectionTest::$UserInLDAP, sizeof($users));

    }
    public function testremoveUserFunction(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();
        $this->getParam();
        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $userRepo = self::getContainer()->get(UserRepository::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 0'));
        $ldapUserService = $container->get(LdapUserService::class);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local.de'));
        $user2 = $userRepo->findOneBy(array('email'=>'test@local2.de'));
        $lobbyUSer = new LobbyWaitungUser();
        $lobbyUSer->setUser($user);
        $lobbyUSer->setRoom($room);
        $lobbyUSer->setCreatedAt(new \DateTime());
        $lobbyUSer->setUid('test');
        $lobbyUSer->setShowName('test');
        $lobbyUSer->setType('a');
        $em->persist($lobbyUSer);
        $em->flush();

        $wait = new Waitinglist();
        $wait->setCreatedAt(new \DateTime());
        $wait->setRoom($room);
        $wait->setUser($user);

        $em->persist($wait);
        $em->flush();

        $user->addFavorite($room);
        $notification = new Notification();
        $notification->setCreatedAt(new \DateTime());
        $notification->setUser($user);
        $notification->setUrl('test');
        $notification->setText('test');
        $notification->setTitle('test');
        $em->persist($notification);
        $em->flush();

        $user->addProtoypeRoom($room);

        $attr = new RoomsUser();
        $attr->setUser($user);
        $attr->setRoom($room);
        $attr->setLobbyModerator(true);
        $em->persist($attr);
        $em->flush();

        $user->addAddressbookInverse($user2);
        $user->addAddressbook($user2);
        $user->getServers()[0]->addUser($user2);
        $ldapUserService->deleteUser($user);

        self::assertNull($userRepo->findOneBy(array('email'=>'test@local.de')));
    }
    private function getParam()
    {
        $para = self::getContainer()->get(ParameterBagInterface::class);
        $this->LDAPURL = $para->get('ldap_test_url');
    }

}
