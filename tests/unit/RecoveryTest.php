<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
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

namespace OCA\Encryption\Tests;

use OC\Files\FileInfo;
use OC\Files\Mount\MountPoint;
use OCA\Encryption\Recovery;
use OCP\Files\Cache\ICacheEntry;
use OCA\Files_Sharing\ISharedStorage;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use Test\TestCase;

class RecoveryTest extends TestCase {
	private static $tempStorage = [];
	/**
	 * @var \OCP\Encryption\IFile|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $fileMock;
	/**
	 * @var \OC\Files\View|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $viewMock;
	/**
	 * @var \OCP\IUserSession|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $userSessionMock;
	/**
	 * @var \OCA\Encryption\KeyManager|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $keyManagerMock;
	/**
	 * @var \OCP\IConfig|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $configMock;
	/**
	 * @var \OCA\Encryption\Crypto\Crypt|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $cryptMock;
	/**
	 * @var Recovery
	 */
	private $instance;

	public function testEnableAdminRecoverySuccessful() {
		$this->keyManagerMock->expects($this->exactly(2))
			->method('recoveryKeyExists')
			->willReturnOnConsecutiveCalls(false, true);

		$this->cryptMock->expects($this->once())
			->method('createKeyPair')
			->willReturn([
				'publicKey' => 'privateKey',
				'privateKey' => 'publicKey',
			]);

		$this->keyManagerMock->expects($this->once())
			->method('setRecoveryKey')
			->willReturn(false);

		$this->keyManagerMock->expects($this->exactly(2))
			->method('checkRecoveryPassword')
			->willReturnOnConsecutiveCalls(true, true);

		$this->assertTrue($this->instance->enableAdminRecovery('password'));
		$this->assertArrayHasKey('recoveryAdminEnabled', self::$tempStorage);
		$this->assertEquals(1, self::$tempStorage['recoveryAdminEnabled']);

		$this->assertTrue($this->instance->enableAdminRecovery('password'));
	}

	public function testEnableAdminRecoveryCouldNotCheckPassword() {
		$this->keyManagerMock->expects($this->exactly(2))
			->method('recoveryKeyExists')
			->willReturnOnConsecutiveCalls(false, true);

		$this->cryptMock->expects($this->once())
			->method('createKeyPair')
			->willReturn([
					'publicKey' => 'privateKey',
					'privateKey' => 'publicKey',
			]);

		$this->keyManagerMock->expects($this->once())
			->method('setRecoveryKey')
			->willReturn(false);

		$this->keyManagerMock->expects($this->exactly(2))
			->method('checkRecoveryPassword')
			->willReturnOnConsecutiveCalls(true, false);

		$this->assertTrue($this->instance->enableAdminRecovery('password'));
		$this->assertArrayHasKey('recoveryAdminEnabled', self::$tempStorage);
		$this->assertEquals(1, self::$tempStorage['recoveryAdminEnabled']);

		$this->assertFalse($this->instance->enableAdminRecovery('password'));
	}

	public function testEnableAdminRecoveryCouldNotCreateKey() {
		$this->keyManagerMock->expects($this->once())
			->method('recoveryKeyExists')
			->willReturn(false);

		$this->cryptMock->expects($this->once())
			->method('createKeyPair')
			->willReturn(false);

		$this->assertFalse($this->instance->enableAdminRecovery('password'));
	}

	public function testChangeRecoveryKeyPasswordSuccessful() {
		$this->assertFalse($this->instance->changeRecoveryKeyPassword(
			'password',
			'passwordOld'
		));

		$this->keyManagerMock->expects($this->once())
			->method('getSystemPrivateKey');

		$this->cryptMock->expects($this->once())
			->method('decryptPrivateKey');

		$this->cryptMock->expects($this->once())
			->method('encryptPrivateKey')
			->willReturn(true);

		$this->assertTrue($this->instance->changeRecoveryKeyPassword(
			'password',
			'passwordOld'
		));
	}

	public function testChangeRecoveryKeyPasswordCouldNotDecryptPrivateRecoveryKey() {
		$this->assertFalse($this->instance->changeRecoveryKeyPassword('password', 'passwordOld'));

		$this->keyManagerMock->expects($this->once())
			->method('getSystemPrivateKey');

		$this->cryptMock->expects($this->once())
			->method('decryptPrivateKey')
			->will($this->returnValue(false));

		$this->assertFalse($this->instance->changeRecoveryKeyPassword('password', 'passwordOld'));
	}

	public function testDisableAdminRecovery() {
		$this->keyManagerMock->expects($this->exactly(2))
			->method('checkRecoveryPassword')
			->willReturnOnConsecutiveCalls(true, false);

		$this->assertArrayHasKey('recoveryAdminEnabled', self::$tempStorage);
		$this->assertTrue($this->instance->disableAdminRecovery('password'));
		$this->assertEquals(0, self::$tempStorage['recoveryAdminEnabled']);

		$this->assertFalse($this->instance->disableAdminRecovery('password'));
	}

	public function testIsRecoveryEnabledForUser() {
		$this->configMock->expects($this->exactly(2))
			->method('getUserValue')
			->willReturnOnConsecutiveCalls('1', '0');

		$this->assertTrue($this->instance->isRecoveryEnabledForUser());
		$this->assertFalse($this->instance->isRecoveryEnabledForUser('admin'));
	}

	public function testIsRecoveryKeyEnabled() {
		$this->assertFalse($this->instance->isRecoveryKeyEnabled());
		self::$tempStorage['recoveryAdminEnabled'] = '1';
		$this->assertTrue($this->instance->isRecoveryKeyEnabled());
	}

	public function testSetRecoveryFolderForUser() {
		$this->viewMock->expects($this->exactly(2))
			->method('getDirectoryContent')
			->willReturn([]);
		$this->assertTrue($this->instance->setRecoveryForUser(0));
		$this->assertTrue($this->instance->setRecoveryForUser('1'));
	}

	public function testRecoverUserFiles() {
		$this->viewMock->expects($this->once())
			->method('getDirectoryContent')
			->willReturn([]);

		$this->cryptMock->expects($this->once())
			->method('decryptPrivateKey');
		$this->instance->recoverUsersFiles('password', 'admin');
		$this->assertTrue(true);
	}

	public function testRecoverFile() {
		$this->keyManagerMock->expects($this->once())
			->method('getEncryptedFileKey')
			->willReturn(true);

		$this->keyManagerMock->expects($this->once())
			->method('getShareKey')
			->willReturn(true);

		$this->cryptMock->expects($this->once())
			->method('multiKeyDecrypt')
			->willReturn(true);

		$this->fileMock->expects($this->once())
			->method('getAccessList')
			->willReturn(['users' => ['admin']]);

		$this->keyManagerMock->expects($this->once())
			->method('getPublicKey')
			->willReturn('publicKey');

		$this->keyManagerMock->expects($this->once())
			->method('addSystemKeys')
			->with($this->anything(), $this->anything(), $this->equalTo('admin'))
			->willReturn(['admin' => 'publicKey']);

		$this->cryptMock->expects($this->once())
			->method('multiKeyEncrypt');

		$this->keyManagerMock->expects($this->once())
			->method('setAllFileKeys');

		$this->assertNull(self::invokePrivate(
			$this->instance,
			'recoverFile',
			['/', 'testkey', 'admin']
		));
	}

	public function testNormalShareOrFedShareRecoveryKeySkipped() {
		$storage = $this->createMock(IStorage::class);
		$storage->method('instanceOfStorage')
			->with(ISharedStorage::class)
			->willReturn(true);

		$cacheEntry = $this->createMock(ICacheEntry::class);
		$mountPoint = $this->createMock(MountPoint::class);
		$mountPoint->method('getStorage')
			->willReturn($storage);

		$user = $this->createMock(IUser::class);
		$remoteUser = $this->createMock(IUser::class);
		$normalShare = new FileInfo('test/files/foo', $storage, '', $cacheEntry, $mountPoint, $user);
		$fedShare = new FileInfo('test/files/fedshare', $storage, '', $cacheEntry, $mountPoint, $remoteUser);

		$this->viewMock->method('getDirectoryContent')
			->willReturn([$normalShare, $fedShare]);
		$this->assertEquals(true, $this->instance->setRecoveryForUser('1'));
	}

	protected function setUp(): void {
		parent::setUp();

		$this->userSessionMock = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->setMethods([
				'isLoggedIn',
				'getUID',
				'login',
				'logout',
				'setUser',
				'getUser'
			])
			->getMock();

		$this->userSessionMock->expects($this->any())->method('getUID')->will($this->returnValue('admin'));

		$this->userSessionMock->expects($this->any())
			->method($this->anything())
			->will($this->returnSelf());

		$this->cryptMock = $this->getMockBuilder('OCA\Encryption\Crypto\Crypt')->disableOriginalConstructor()->getMock();
		/** @var \OCP\Security\ISecureRandom $randomMock */
		$randomMock = $this->createMock('OCP\Security\ISecureRandom');
		$this->keyManagerMock = $this->getMockBuilder('OCA\Encryption\KeyManager')->disableOriginalConstructor()->getMock();
		$this->configMock = $this->createMock('OCP\IConfig');
		/** @var \OCP\Encryption\Keys\IStorage $keyStorageMock */
		$keyStorageMock = $this->createMock('OCP\Encryption\Keys\IStorage');
		$this->fileMock = $this->createMock('OCP\Encryption\IFile');
		$this->viewMock = $this->createMock('OC\Files\View');

		$this->configMock->expects($this->any())
			->method('setAppValue')
			->will($this->returnCallback([$this, 'setValueTester']));

		$this->configMock->expects($this->any())
			->method('getAppValue')
			->will($this->returnCallback([$this, 'getValueTester']));

		$this->instance = new Recovery(
			$this->userSessionMock,
			$this->cryptMock,
			$randomMock,
			$this->keyManagerMock,
			$this->configMock,
			$keyStorageMock,
			$this->fileMock,
			$this->viewMock
		);
	}

	/**
	 * @param $app
	 * @param $key
	 * @param $value
	 */
	public function setValueTester($app, $key, $value) {
		self::$tempStorage[$key] = $value;
	}

	/**
	 * @param $key
	 */
	public function removeValueTester($key) {
		unset(self::$tempStorage[$key]);
	}

	/**
	 * @param $app
	 * @param $key
	 * @return mixed
	 */
	public function getValueTester($app, $key) {
		if (!empty(self::$tempStorage[$key])) {
			return self::$tempStorage[$key];
		}
		return null;
	}
}
