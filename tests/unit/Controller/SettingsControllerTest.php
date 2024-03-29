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

namespace OCA\Encryption\Tests\Controller;

use OCA\Encryption\Controller\SettingsController;
use OCA\Encryption\Session;
use OCP\AppFramework\Http;
use Test\TestCase;

class SettingsControllerTest extends TestCase {
	/** @var SettingsController */
	private $controller;

	/** @var \OCP\IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $requestMock;

	/** @var \OCP\IL10N|\PHPUnit\Framework\MockObject\MockObject */
	private $l10nMock;

	/** @var \OCP\IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	private $userManagerMock;

	/** @var \OCP\IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	private $userSessionMock;

	/** @var \OCA\Encryption\KeyManager|\PHPUnit\Framework\MockObject\MockObject */
	private $keyManagerMock;

	/** @var \OCA\Encryption\Crypto\Crypt|\PHPUnit\Framework\MockObject\MockObject */
	private $cryptMock;

	/** @var \OCA\Encryption\Session|\PHPUnit\Framework\MockObject\MockObject */
	private $sessionMock;

	/** @var \OCP\ISession|\PHPUnit\Framework\MockObject\MockObject */
	private $ocSessionMock;

	/** @var \OCA\Encryption\Util|\PHPUnit\Framework\MockObject\MockObject */
	private $utilMock;

	protected function setUp(): void {
		parent::setUp();

		$this->requestMock = $this->createMock('OCP\IRequest');

		$this->l10nMock = $this->getMockBuilder('OCP\IL10N')
			->disableOriginalConstructor()->getMock();

		$this->l10nMock->expects($this->any())
			->method('t')
			->will($this->returnCallback(function ($message) {
				return $message;
			}));

		$this->userManagerMock = $this->getMockBuilder('OCP\IUserManager')
			->disableOriginalConstructor()->getMock();

		$this->keyManagerMock = $this->getMockBuilder('OCA\Encryption\KeyManager')
			->disableOriginalConstructor()->getMock();

		$this->cryptMock = $this->getMockBuilder('OCA\Encryption\Crypto\Crypt')
			->disableOriginalConstructor()->getMock();

		$this->userSessionMock = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->setMethods([
				'isLoggedIn',
				'getUID',
				'login',
				'logout',
				'setUser',
				'getUser',
				'canChangePassword',
			])
			->getMock();

		$this->ocSessionMock = $this->getMockBuilder('OCP\ISession')->disableOriginalConstructor()->getMock();

		$this->userSessionMock->expects($this->any())
			->method('getUID')
			->willReturn('testUserUid');

		$this->userSessionMock->expects($this->any())
			->method($this->anything())
			->will($this->returnSelf());

		$this->sessionMock = $this->getMockBuilder('OCA\Encryption\Session')
			->disableOriginalConstructor()->getMock();

		$this->utilMock = $this->getMockBuilder('OCA\Encryption\Util')
			->disableOriginalConstructor()
			->getMock();

		$this->controller = new SettingsController(
			'encryption',
			$this->requestMock,
			$this->l10nMock,
			$this->userManagerMock,
			$this->userSessionMock,
			$this->keyManagerMock,
			$this->cryptMock,
			$this->sessionMock,
			$this->ocSessionMock,
			$this->utilMock
		);
	}

	/**
	 * test updatePrivateKeyPassword() if wrong new password was entered
	 */
	public function testUpdatePrivateKeyPasswordWrongNewPassword() {
		$oldPassword = 'old';
		$newPassword = 'new';

		$this->userSessionMock->expects($this->once())->method('getUID')->willReturn('uid');

		$this->userManagerMock
			->expects($this->exactly(2))
			->method('checkPassword')
			->willReturn(false);

		$result = $this->controller->updatePrivateKeyPassword($oldPassword, $newPassword);

		$data = $result->getData();

		$this->assertSame(Http::STATUS_BAD_REQUEST, $result->getStatus());
		$this->assertSame(
			'The current log-in password was not correct, please try again.',
			$data['message']
		);
	}

	/**
	 * test updatePrivateKeyPassword() if wrong old password was entered
	 */
	public function testUpdatePrivateKeyPasswordWrongOldPassword() {
		$oldPassword = 'old';
		$newPassword = 'new';

		$this->userManagerMock
			->expects($this->once())
			->method('checkPassword')
			->willReturn(true);

		$this->cryptMock
			->expects($this->once())
			->method('decryptPrivateKey')
			->willReturn(false);

		$result = $this->controller->updatePrivateKeyPassword($oldPassword, $newPassword);

		$data = $result->getData();

		$this->assertSame(Http::STATUS_BAD_REQUEST, $result->getStatus());
		$this->assertSame(
			'The old password was not correct, please try again.',
			$data['message']
		);
	}

	/**
	 * test updatePrivateKeyPassword() with the correct old and new password
	 */
	public function testUpdatePrivateKeyPassword() {
		$oldPassword = 'old';
		$newPassword = 'new';

		$this->ocSessionMock->expects($this->once())
			->method('get')->with('loginname')->willReturn('testUser');

		$this->userManagerMock
			->expects($this->exactly(2))
			->method('checkPassword')
			->withConsecutive(
				['testUserUid', 'new'],
				['testUser', 'new'],
			)
			->willReturnOnConsecutiveCalls(false, true);

		$this->cryptMock
			->expects($this->once())
			->method('decryptPrivateKey')
			->willReturn('decryptedKey');

		$this->cryptMock
			->expects($this->once())
			->method('encryptPrivateKey')
			->willReturn('encryptedKey');

		$this->cryptMock
			->expects($this->once())
			->method('generateHeader')
			->willReturn('header.');

		// methods which must be called after successful changing the key password
		$this->keyManagerMock
			->expects($this->once())
			->method('setPrivateKey')
			->with($this->equalTo('testUserUid'), $this->equalTo('header.encryptedKey'));

		$this->sessionMock
			->expects($this->once())
			->method('setPrivateKey')
			->with($this->equalTo('decryptedKey'));

		$this->sessionMock
			->expects($this->once())
			->method('setStatus')
			->with($this->equalTo(Session::INIT_SUCCESSFUL));

		$result = $this->controller->updatePrivateKeyPassword($oldPassword, $newPassword);

		$data = $result->getData();

		$this->assertSame(Http::STATUS_OK, $result->getStatus());
		$this->assertSame(
			'Private key password successfully updated.',
			$data['message']
		);
	}

	public function testSetEncryptHomeStorage() {
		$value = true;
		$this->utilMock->expects($this->once())->method('setEncryptHomeStorage')->with($value);
		$this->controller->setEncryptHomeStorage($value);
	}
}
