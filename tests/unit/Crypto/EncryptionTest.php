<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
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

namespace OCA\Encryption\Tests\Crypto;

use OCA\Encryption\Exceptions\PublicKeyMissingException;
use Test\TestCase;
use OCA\Encryption\Crypto\Encryption;

class EncryptionTest extends TestCase {
	/** @var Encryption */
	private $instance;

	/** @var \OCA\Encryption\KeyManager|\PHPUnit\Framework\MockObject\MockObject */
	private $keyManagerMock;

	/** @var \OCA\Encryption\Crypto\EncryptAll|\PHPUnit\Framework\MockObject\MockObject */
	private $encryptAllMock;

	/** @var \OCA\Encryption\Crypto\DecryptAll|\PHPUnit\Framework\MockObject\MockObject */
	private $decryptAllMock;

	/** @var \OCA\Encryption\Session|\PHPUnit\Framework\MockObject\MockObject */
	private $sessionMock;

	/** @var \OCA\Encryption\Crypto\Crypt|\PHPUnit\Framework\MockObject\MockObject */
	private $cryptMock;

	/** @var \OCA\Encryption\Util|\PHPUnit\Framework\MockObject\MockObject */
	private $utilMock;

	/** @var \OCP\ILogger|\PHPUnit\Framework\MockObject\MockObject */
	private $loggerMock;

	/** @var \OCP\IL10N|\PHPUnit\Framework\MockObject\MockObject */
	private $l10nMock;

	/** @var \OCP\Files\Storage|\PHPUnit\Framework\MockObject\MockObject */
	private $storageMock;

	public function setUp(): void {
		parent::setUp();

		$this->storageMock = $this->getMockBuilder('OCP\Files\Storage')
			->disableOriginalConstructor()->getMock();
		$this->cryptMock = $this->getMockBuilder('OCA\Encryption\Crypto\Crypt')
			->disableOriginalConstructor()
			->getMock();
		$this->utilMock = $this->getMockBuilder('OCA\Encryption\Util')
			->disableOriginalConstructor()
			->getMock();
		$this->keyManagerMock = $this->getMockBuilder('OCA\Encryption\KeyManager')
			->disableOriginalConstructor()
			->getMock();
		$this->sessionMock = $this->getMockBuilder('OCA\Encryption\Session')
			->disableOriginalConstructor()
			->getMock();
		$this->encryptAllMock = $this->getMockBuilder('OCA\Encryption\Crypto\EncryptAll')
			->disableOriginalConstructor()
			->getMock();
		$this->decryptAllMock = $this->getMockBuilder('OCA\Encryption\Crypto\DecryptAll')
			->disableOriginalConstructor()
			->getMock();
		$this->loggerMock = $this->getMockBuilder('OCP\ILogger')
			->disableOriginalConstructor()
			->getMock();
		$this->l10nMock = $this->getMockBuilder('OCP\IL10N')
			->disableOriginalConstructor()
			->getMock();
		$this->l10nMock->expects($this->any())
			->method('t')
			->with($this->anything())
			->willReturnArgument(0);

		$this->instance = new Encryption(
			$this->cryptMock,
			$this->keyManagerMock,
			$this->utilMock,
			$this->sessionMock,
			$this->encryptAllMock,
			$this->decryptAllMock,
			$this->loggerMock,
			$this->l10nMock
		);
	}

	/**
	 * test if public key from one of the recipients is missing
	 */
	public function testEndUser1() {
		$this->instance->begin('/foo/bar', 'user1', 'r', [], ['users' => ['user1', 'user2', 'user3']]);
		$this->endTest();
	}

	/**
	 * test if public key from owner is missing
	 *
	 */
	public function testEndUser2() {
		$this->expectException(\OCA\Encryption\Exceptions\PublicKeyMissingException::class);

		$this->instance->begin('/foo/bar', 'user2', 'r', [], ['users' => ['user1', 'user2', 'user3']]);
		$this->endTest();
	}

	/**
	 * common part of testEndUser1 and testEndUser2
	 *
	 * @throws PublicKeyMissingException
	 */
	public function endTest() {
		// prepare internal variables
		self::invokePrivate($this->instance, 'isWriteOperation', [true]);
		self::invokePrivate($this->instance, 'writeCache', ['']);

		$this->keyManagerMock->expects($this->any())
			->method('getPublicKey')
			->will($this->returnCallback([$this, 'getPublicKeyCallback']));
		$this->keyManagerMock->expects($this->any())
			->method('addSystemKeys')
			->will($this->returnCallback([$this, 'addSystemKeysCallback']));
		$this->cryptMock->expects($this->any())
			->method('multiKeyEncrypt')
			->willReturn(true);

		$this->instance->end('/foo/bar');
	}

	public function getPublicKeyCallback($uid) {
		if ($uid === 'user2') {
			throw new PublicKeyMissingException($uid);
		}
		return $uid;
	}

	public function addSystemKeysCallback($accessList, $publicKeys) {
		$this->assertSame(2, \count($publicKeys));
		$this->assertArrayHasKey('user1', $publicKeys);
		$this->assertArrayHasKey('user3', $publicKeys);
		return $publicKeys;
	}

	/**
	 * @dataProvider dataProviderForTestGetPathToRealFile
	 */
	public function testGetPathToRealFile($path, $expected) {
		$this->assertSame(
			$expected,
			self::invokePrivate($this->instance, 'getPathToRealFile', [$path])
		);
	}

	public function dataProviderForTestGetPathToRealFile() {
		return [
			['/user/files/foo/bar.txt', '/user/files/foo/bar.txt'],
			['/user/files/foo.txt', '/user/files/foo.txt'],
			['/user/files_versions/foo.txt.v543534', '/user/files/foo.txt'],
			['/user/files_versions/foo/bar.txt.v5454', '/user/files/foo/bar.txt'],
			['/user/files_trashbin/versions/foo.txt.v543534.d85747', '/user/files_trashbin/files/foo.txt.d85747'],
			['/user/files_trashbin/versions/foo/bar.txt.v5454.d918273', '/user/files_trashbin/files/foo/bar.txt.d918273'],
		];
	}

	/**
	 * @dataProvider dataTestBegin
	 */
	public function testBegin($mode, $header, $legacyCipher, $defaultCipher, $fileKey, $expected) {
		$this->sessionMock->expects($this->once())
			->method('decryptAllModeActivated')
			->willReturn(false);

		$this->sessionMock->expects($this->never())->method('getDecryptAllUid');
		$this->sessionMock->expects($this->never())->method('getDecryptAllKey');
		$this->keyManagerMock->expects($this->never())->method('getEncryptedFileKey');
		$this->keyManagerMock->expects($this->never())->method('getShareKey');
		$this->cryptMock->expects($this->never())->method('multiKeyDecrypt');

		$this->cryptMock->expects($this->any())
			->method('getCipher')
			->willReturn($defaultCipher);
		$this->cryptMock->expects($this->any())
			->method('getLegacyCipher')
			->willReturn($legacyCipher);
		if (empty($fileKey)) {
			$this->cryptMock->expects($this->once())
				->method('generateFileKey')
				->willReturn('fileKey');
		} else {
			$this->cryptMock->expects($this->never())
				->method('generateFileKey');
		}

		$this->keyManagerMock->expects($this->once())
			->method('getFileKey')
			->willReturn($fileKey);

		$result = $this->instance->begin('/user/files/foo.txt', 'user', $mode, $header, []);

		$this->assertArrayHasKey('cipher', $result);
		$this->assertSame($expected, $result['cipher']);
		if ($mode === 'w') {
			$this->assertTrue(self::invokePrivate($this->instance, 'isWriteOperation'));
		} else {
			$this->assertFalse(self::invokePrivate($this->instance, 'isWriteOperation'));
		}
	}

	public function dataTestBegin() {
		return [
			['w', ['cipher' => 'myCipher'], 'legacyCipher', 'defaultCipher', 'fileKey', 'defaultCipher'],
			['r', ['cipher' => 'myCipher'], 'legacyCipher', 'defaultCipher', 'fileKey', 'myCipher'],
			['w', [], 'legacyCipher', 'defaultCipher', '', 'defaultCipher'],
			['r', [], 'legacyCipher', 'defaultCipher', 'file_key', 'legacyCipher'],
		];
	}

	/**
	 * test begin() if decryptAll mode was activated
	 */
	public function testBeginDecryptAll() {
		$path = '/user/files/foo.txt';
		$recoveryKeyId = 'recoveryKeyId';
		$recoveryShareKey = 'recoveryShareKey';
		$decryptAllKey = 'decryptAllKey';
		$fileKey = 'fileKey';

		$this->sessionMock->expects($this->once())
			->method('decryptAllModeActivated')
			->willReturn(true);
		$this->sessionMock->expects($this->once())
			->method('getDecryptAllUid')
			->willReturn($recoveryKeyId);
		$this->sessionMock->expects($this->once())
			->method('getDecryptAllKey')
			->willReturn($decryptAllKey);

		$this->keyManagerMock->expects($this->once())
			->method('getEncryptedFileKey')
			->willReturn('encryptedFileKey');
		$this->keyManagerMock->expects($this->once())
			->method('getShareKey')
			->with($path, $recoveryKeyId)
			->willReturn($recoveryShareKey);
		$this->cryptMock->expects($this->once())
			->method('multiKeyDecrypt')
			->with('encryptedFileKey', $recoveryShareKey, $decryptAllKey)
			->willReturn($fileKey);

		$this->keyManagerMock->expects($this->never())->method('getFileKey');

		$this->instance->begin($path, 'user', 'r', [], []);

		$this->assertSame(
			$fileKey,
			$this->invokePrivate($this->instance, 'fileKey')
		);
	}

	/**
	 * @dataProvider dataTestUpdate
	 *
	 * @param string $fileKey
	 * @param boolean $expected
	 */
	public function testUpdate($fileKey, $expected) {
		$this->keyManagerMock->expects($this->once())
			->method('getFileKey')->willReturn($fileKey);

		$this->keyManagerMock->expects($this->any())
			->method('getPublicKey')->willReturn('publicKey');

		$this->keyManagerMock->expects($this->any())
			->method('addSystemKeys')
			->willReturnCallback(function ($accessList, $publicKeys) {
				return $publicKeys;
			});

		$this->keyManagerMock->expects($this->never())->method('getVersion');
		$this->keyManagerMock->expects($this->never())->method('setVersion');

		$this->assertSame(
			$expected,
			$this->instance->update('path', 'user1', ['users' => ['user1']])
		);
	}

	public function dataTestUpdate() {
		return [
			['', false],
			['fileKey', true]
		];
	}

	public function testUpdateNoUsers() {
		$this->invokePrivate($this->instance, 'rememberVersion', [['path' => 2]]);

		$this->keyManagerMock->expects($this->never())->method('getFileKey');
		$this->keyManagerMock->expects($this->never())->method('getPublicKey');
		$this->keyManagerMock->expects($this->never())->method('addSystemKeys');
		$this->keyManagerMock->expects($this->once())->method('setVersion')
			->willReturnCallback(function ($path, $version, $view) {
				$this->assertSame('path', $path);
				$this->assertSame(2, $version);
				$this->assertTrue($view instanceof \OC\Files\View);
			});
		$this->instance->update('path', 'user1', []);
	}

	/**
	 * Test case if the public key is missing. ownCloud should still encrypt
	 * the file for the remaining users
	 */
	public function testUpdateMissingPublicKey() {
		$this->keyManagerMock->expects($this->once())
			->method('getFileKey')->willReturn('fileKey');

		$this->keyManagerMock->expects($this->any())
			->method('getPublicKey')->willReturnCallback(
				function ($user) {
					throw new PublicKeyMissingException($user);
				}
			);

		$this->keyManagerMock->expects($this->any())
			->method('addSystemKeys')
			->willReturnCallback(function ($accessList, $publicKeys) {
				return $publicKeys;
			});

		$this->cryptMock->expects($this->once())->method('multiKeyEncrypt')
			->willReturnCallback(
				function ($fileKey, $publicKeys) {
					$this->assertEmpty($publicKeys);
					$this->assertSame('fileKey', $fileKey);
				}
			);

		$this->keyManagerMock->expects($this->never())->method('getVersion');
		$this->keyManagerMock->expects($this->never())->method('setVersion');

		$this->assertTrue(
			$this->instance->update('path', 'user1', ['users' => ['user1']])
		);
	}

	/**
	 * by default the encryption module should encrypt regular files, files in
	 * files_versions and files in files_trashbin
	 *
	 * @dataProvider dataTestShouldEncrypt
	 */
	public function testShouldEncrypt($path, $shouldEncryptHomeStorage, $isHomeStorage, $expected) {
		$this->utilMock->expects($this->once())->method('shouldEncryptHomeStorage')
			->willReturn($shouldEncryptHomeStorage);

		if ($shouldEncryptHomeStorage === false) {
			$this->storageMock->expects($this->once())->method('instanceOfStorage')
				->with('\OCP\Files\IHomeStorage')->willReturn($isHomeStorage);
			$this->utilMock->expects($this->once())->method('getStorage')->with($path)
				->willReturn($this->storageMock);
		}

		$this->assertSame(
			$expected,
			$this->instance->shouldEncrypt($path)
		);
	}

	public function dataTestShouldEncrypt() {
		return [
			['/user1/files/foo.txt', true, true, true],
			['/user1/files_versions/foo.txt', true, true, true],
			['/user1/files_trashbin/foo.txt', true, true, true],
			['/user1/some_folder/foo.txt', true, true, false],
			['/user1/foo.txt', true, true, false],
			['/user1/files', true, true, false],
			['/user1/files_trashbin', true, true, false],
			['/user1/files_versions', true, true, false],
			// test if shouldEncryptHomeStorage is set to false
			['/user1/files/foo.txt', false, true, false],
			['/user1/files_versions/foo.txt', false, false, true],
		];
	}

	/**
	 */
	public function testDecrypt() {
		$this->expectException(\OC\Encryption\Exceptions\DecryptionFailedException::class);
		$this->expectExceptionMessage('Can not decrypt this file, probably this is a shared file. Please ask the file owner to reshare the file with you.');

		$this->instance->decrypt('abc');
	}

	public function testPrepareDecryptAll() {
		/** @var \Symfony\Component\Console\Input\InputInterface $input */
		$input = $this->createMock('Symfony\Component\Console\Input\InputInterface');
		/** @var \Symfony\Component\Console\Output\OutputInterface $output */
		$output = $this->createMock('Symfony\Component\Console\Output\OutputInterface');

		$this->decryptAllMock->expects($this->once())->method('prepare')
			->with($input, $output, 'user');

		$this->instance->prepareDecryptAll($input, $output, 'user');
	}
}
