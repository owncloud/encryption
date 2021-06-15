<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2020, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Encryption\Tests\Hooks;

use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\Hooks\UserHooks;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Recovery;
use OCA\Encryption\Session;
use OCA\Encryption\Users\Setup;
use OCA\Encryption\Util;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;
use Test\TestCase;

/**
 * Class UserHooksTest
 *
 * @group DB
 * @package OCA\Encryption\Tests\Hooks
 */
class UserHooksTest extends TestCase {
	/**
	 * @var MockObject
	 */
	private $utilMock;
	/**
	 * @var MockObject
	 */
	private $recoveryMock;
	/**
	 * @var MockObject
	 */
	private $sessionMock;
	/**
	 * @var MockObject
	 */
	private $keyManagerMock;
	/**
	 * @var MockObject
	 */
	private $userManagerMock;

	/**
	 * @var MockObject
	 */
	private $userSetupMock;
	/**
	 * @var MockObject
	 */
	private $userSessionMock;
	/**
	 * @var MockObject
	 */
	private $cryptMock;
	/**
	 * @var MockObject
	 */
	private $loggerMock;

	private $config;
	/**
	 * @var UserHooks
	 */
	private $instance;

	/** @var  EventDispatcher */
	private $eventDispatcher;

	private $params;

	private $keyPair = ['publicKey' => 'abcd', 'privateKey' => 'efgh'];

	public function testLogin() {
		$this->userSetupMock->expects($this->once())
			->method('setupUser')
			->willReturn(true);

		$this->keyManagerMock->expects($this->once())
			->method('init')
			->with('testUser', 'password');

		$this->config->expects($this->once())
			->method('getAppValue')
			->willReturnMap([
				['encryption', 'userSpecificKey', '', ''],
			]);

		$this->assertNull($this->instance->login($this->params));
	}

	public function testLogout() {
		$this->sessionMock->expects($this->once())
			->method('clear');
		$this->instance->logout();
		$this->assertTrue(true);
	}

	public function testPostCreateUser() {
		$this->userSetupMock->expects($this->once())
			->method('setupUser');

		$this->instance->postCreateUser($this->params);
		$this->assertTrue(true);
	}

	public function testPostDeleteUser() {
		$this->keyManagerMock->expects($this->once())
			->method('deletePublicKey')
			->with('testUser');

		$this->assertNull($this->instance->postDeleteUser($this->params));
	}

	public function dataTestPreSetPassphrase() {
		return [
			[true],
			[false]
		];
	}

	/**
	 * @dataProvider dataTestPreSetPassphrase
	 */
	public function testPreSetPassphrase($canChange) {
		/** @var UserHooks | MockObject  $instance */
		$instance = $this->getInstanceMock(['setPassphrase']);
		$userMock = $this->params['user'];
		$userMock->expects($this->once())
			->method('canChangePassword')
			->willReturn($canChange);

		if ($canChange) {
			// in this case the password will be changed in the post hook
			$instance->expects($this->never())->method('setPassphrase');
		} else {
			// if user can't change the password we update the encryption
			// key password already in the pre hook
			$instance->expects($this->once())
				->method('setPassphrase')
				->with($this->params);
		}

		$this->assertNull($instance->preSetPassphrase($this->params));
	}

	public function testSetPassphrase() {
		$this->sessionMock->expects($this->exactly(4))
			->method('getPrivateKey')
			->willReturnOnConsecutiveCalls(true, false, false, false);

		$this->cryptMock->expects($this->exactly(4))
			->method('encryptPrivateKey')
			->willReturn(true);

		$this->cryptMock->expects($this->any())
			->method('generateHeader')
			->willReturn(Crypt::HEADER_START . ':Cipher:test:' . Crypt::HEADER_END);

		$this->cryptMock->expects($this->any())
			->method('createKeyPair')
			->willReturn($this->keyPair);

		$this->keyManagerMock->expects($this->exactly(4))
			->method('setPrivateKey')
			->willReturnCallback(function ($user, $key) {
				$header = \substr($key, 0, \strlen(Crypt::HEADER_START));
				$this->assertSame(
					Crypt::HEADER_START,
					$header,
					'every encrypted file should start with a header'
				);
			});

		$this->assertNull($this->instance->setPassphrase($this->params));
		$this->params['recoveryPassword'] = 'password';

		$this->recoveryMock->expects($this->exactly(3))
			->method('isRecoveryEnabledForUser')
			->with('testUser')
			->willReturnOnConsecutiveCalls(true, false, false);

		$this->instance = $this->getInstanceMock(['initMountPoints']);

		$this->instance->expects($this->exactly(3))->method('initMountPoints');

		// Test first if statement
		$this->assertNull($this->instance->setPassphrase($this->params));

		// Test Second if conditional
		$this->keyManagerMock->expects($this->exactly(2))
			->method('userHasKeys')
			->with('testUser')
			->willReturn(true);

		$this->assertNull($this->instance->setPassphrase($this->params));

		// Test third and final if condition
		$this->utilMock->expects($this->once())
			->method('userHasFiles')
			->with('testUser')
			->willReturn(false);

		$this->keyManagerMock->expects($this->once())
			->method('setPrivateKey');

		$this->recoveryMock->expects($this->once())
			->method('recoverUsersFiles')
			->with('password', 'testUser');

		$this->assertNull($this->instance->setPassphrase($this->params));
	}

	/**
	 * Test setPassphrase without session and no logger error should appear
	 */
	public function testSetPassphraseWithoutSession() {
		/** @var UserHooks $userHooks */
		$userHooks = $this->getInstanceMock(['initMountPoints']);

		$this->userSessionMock->expects($this->any())
			->method('getUser')
			->willReturn(null);

		$this->cryptMock->expects($this->any())
			->method('encryptPrivateKey')
			->willReturn(true);

		$this->cryptMock->expects($this->once())
			->method('createKeyPair')
			->willReturn($this->keyPair);

		$this->keyManagerMock->expects($this->any())
			->method('setPrivateKey')
			->willReturn(true);

		//No logger error should appear
		$this->loggerMock->expects($this->never())
			->method('error');

		$this->assertNull($userHooks->setPassphrase($this->params));
	}

	public function testSetPassphraseWithoutSessionLoggerError() {
		$this->userSessionMock->expects($this->any())
			->method('getUser')
			->willReturn(null);

		$this->cryptMock->expects($this->any())
			->method('createKeyPair')
			->willReturn($this->keyPair);

		$this->cryptMock->expects($this->any())
			->method('encryptPrivateKey')
			->willReturn(false);

		//No logger error should appear
		$this->loggerMock->expects($this->any())
			->method('error')
			->with('Encryption Could not update users encryption password');

		$userHooks = $this->getInstanceMock(['initMountPoints']);

		$this->assertNull($userHooks->setPassphrase($this->params));
	}

	public function testSetPasswordNoUser() {
		$this->sessionMock->expects($this->any())
			->method('getPrivateKey')
			->willReturn(true);

		$this->userSessionMock = $this->createMock(IUserSession::class);
		$this->userSessionMock->expects($this->any())
			->method('getUser')
			->will($this->returnValue(null));

		$this->recoveryMock->expects($this->once())
			->method('isRecoveryEnabledForUser')
			->with('testUser')
			->willReturn(false);

		$this->cryptMock->expects($this->any())
			->method('createKeyPair')
			->willReturn($this->keyPair);

		/** @var UserHooks $userHooks */
		$userHooks = $this->getInstanceMock(['initMountPoints']);

		$this->assertNull($userHooks->setPassphrase($this->params));
	}

	public function testPostPasswordReset() {
		$this->keyManagerMock->expects($this->once())
			->method('deleteUserKeys')
			->with('testUser');

		$this->userSetupMock->expects($this->once())
			->method('setupUser')
			->with('testUser', 'password');

		$this->assertNull($this->instance->postPasswordReset($this->params));
	}

	protected function getInstanceMock($methods) {
		return $this->getMockBuilder(UserHooks::class)
			->setConstructorArgs([
					$this->keyManagerMock,
					$this->userManagerMock,
					$this->loggerMock,
					$this->userSetupMock,
					$this->userSessionMock,
					$this->utilMock,
					$this->sessionMock,
					$this->cryptMock,
					$this->recoveryMock,
					$this->config,
					$this->eventDispatcher
			])
			->onlyMethods($methods)
			->getMock();
	}

	protected function setUp(): void {
		parent::setUp();
		\OC_App::enable('encryption');
		$this->loggerMock = $this->createMock(ILogger::class);
		$this->keyManagerMock = $this->getMockBuilder(KeyManager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userManagerMock = $this->getMockBuilder(IUserManager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSetupMock = $this->getMockBuilder(Setup::class)
			->disableOriginalConstructor()
			->getMock();

		$this->userSessionMock = $this->getMockBuilder(IUserSession::class)
			->disableOriginalConstructor()
			->setMethods([
				'isLoggedIn',
				'getUID',
				'login',
				'logout',
				'setUser',
				'getUser',
				'canChangePassword'
			])
			->getMock();

		$this->userSessionMock->expects($this->any())->method('getUID')->will($this->returnValue('testUser'));

		$this->userSessionMock->expects($this->any())
			->method($this->anything())
			->will($this->returnSelf());

		$sessionMock = $this->getMockBuilder(Session::class)
			->disableOriginalConstructor()
			->getMock();

		$this->cryptMock = $this->getMockBuilder(Crypt::class)
			->disableOriginalConstructor()
			->getMock();
		$recoveryMock = $this->getMockBuilder(Recovery::class)
			->disableOriginalConstructor()
			->getMock();
		$this->config = $this->createMock(IConfig::class);

		$this->sessionMock = $sessionMock;
		$this->recoveryMock = $recoveryMock;
		$this->utilMock = $this->createMock(Util::class);
		$this->utilMock->expects($this->any())->method('isMasterKeyEnabled')->willReturn(false);
		$this->eventDispatcher = $this->createMock(EventDispatcher::class);

		$userMock = $this->createMock(IUser::class);
		$userMock->expects($this->any())->method('getUID')->willReturn('testUser');
		$this->params = new GenericEvent(null, ['uid' => 'testUser', 'password' => 'password', 'user' => $userMock]);
		$this->instance = $this->getInstanceMock(['setupFS']);
	}

	protected function tearDown(): void {
		parent::tearDown();
		\OC_App::disable('encryption');
	}
}
