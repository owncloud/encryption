<?php
/**
 * @author Jannik Stehle <jstehle@owncloud.com>
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

namespace OCA\Encryption\Tests\Command;

use OCA\Encryption\Command\HSMDaemonDecrypt;
use OCA\Encryption\Crypto\CryptHSM;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Util;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;
use Symfony\Component\Console\Helper\QuestionHelper;

class HSMDaemonDecryptTest extends TestCase {
	/** @var IConfig | \PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var IClientService | \PHPUnit\Framework\MockObject\MockObject */
	private $httpClient;
	/** @var ITimeFactory | \PHPUnit\Framework\MockObject\MockObject */
	private $timeFactory;
	/** @var KeyManager | \PHPUnit\Framework\MockObject\MockObject */
	private $keyManager;
	/** @var CryptHSM | \PHPUnit\Framework\MockObject\MockObject */
	private $crypt;
	/** @var Util | \PHPUnit\Framework\MockObject\MockObject */
	private $util;
	/** @var HSMDaemonDecrypt */
	private $hsmDaemonDecrypt;

	public function setUp(): void {
		parent::setUp();
		$this->httpClient = $this->createMock(IClientService::class);
		$this->config = $this->createMock(IConfig::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->keyManager = $this->createMock(KeyManager::class);
		$this->crypt = $this->createMock(CryptHSM::class);
		$this->util = $this->createMock(Util::class);

		$response = $this->createMock(IResponse::class);
		$response->method('getBody')->willReturn('foo');
		$newClient = $this->createMock(IClient::class);
		$newClient->method('post')->willReturn($response);
		$this->httpClient->method('newClient')->willReturn($newClient);

		$this->hsmDaemonDecrypt = $this
			->getMockBuilder('\OCA\Encryption\Command\HSMDaemonDecrypt')
			->setConstructorArgs([
				$this->httpClient,
				$this->config,
				$this->timeFactory,
				$this->keyManager,
				$this->crypt,
				$this->util
			])
			->onlyMethods(['getHelper'])
			->getMock();

		$inputHelper = $this->createMock(QuestionHelper::class);
		$inputHelper->method('ask')->willReturn('password');
		$this->hsmDaemonDecrypt->method('getHelper')->willReturn($inputHelper);
	}

	public function testExecuteWithoutOptionsAndWithoutMasterKey() {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);
		$outputInterface->expects($this->once())->method('writeln')
			->with('<error>please provide either a keyId or a username</error>');

		$this->config->expects($this->once())
			->method('getAppValue')
			->willReturn('http://localhost:1234');
		$inputInterface->expects($this->any())->method('getOption')
			->willReturn(null);
		$this->util->method('isMasterKeyEnabled')
			->willReturn(false);

		$this->invokePrivate($this->hsmDaemonDecrypt, 'execute', [$inputInterface, $outputInterface]);
	}

	public function testExecuteWithoutOptionsButWithMasterKey() {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);
		$outputInterface->expects($this->once())->method('writeln')
			->with("decrypted string (base64 encoded): '". \base64_encode('foo') ."'");

		$this->config->expects($this->exactly(3))
			->method('getAppValue')
			->willReturn('http://localhost:1234', 'masterKeyId', 'jwtSecret');
		$inputInterface->expects($this->any())->method('getOption')
			->willReturn(null);
		$this->util->expects($this->once())
			->method('isMasterKeyEnabled')
			->willReturn(true);
		$this->config->expects($this->exactly(2))
			->method('getSystemValue')
			->willReturn('masterKeyPassword', 'instanceid');
		$this->keyManager->expects($this->once())
			->method('getSystemPrivateKey')
			->willReturn('privateKey');
		$this->crypt->expects($this->once())
			->method('decryptPrivateKey')
			->willReturn('decryptedPrivateKey');

		$this->invokePrivate($this->hsmDaemonDecrypt, 'execute', [$inputInterface, $outputInterface]);
	}

	public function testExecuteWithUsername() {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);
		$outputInterface->expects($this->once())->method('writeln')
			->with("decrypted string (base64 encoded): '". \base64_encode('foo') ."'");

		$this->config->expects($this->exactly(2))
			->method('getAppValue')
			->willReturn('http://localhost:1234', 'jwtSecret');
		$inputInterface->expects($this->any())->method('getOption')
			->willReturn('user', 'user', null, 'user');
		$this->util->expects($this->never())->method('isMasterKeyEnabled');
		$this->keyManager->expects($this->once())
			->method('getPrivateKey')
			->willReturn('privateKey');
		$this->crypt->expects($this->once())
			->method('decryptPrivateKey')
			->willReturn('decryptedPrivateKey');

		$this->invokePrivate($this->hsmDaemonDecrypt, 'execute', [$inputInterface, $outputInterface]);
	}

	public function testExecuteWithKeyId() {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);
		$outputInterface->expects($this->once())->method('writeln')
			->with("decrypted string (base64 encoded): '". \base64_encode('foo') ."'");

		$this->config->expects($this->exactly(2))
			->method('getAppValue')
			->willReturn('http://localhost:1234', 'jwtSecret');
		$inputInterface->expects($this->exactly(2))->method('getOption')
			->willReturn(null, 'keyId');
		$this->util->expects($this->never())->method('isMasterKeyEnabled');

		$this->invokePrivate($this->hsmDaemonDecrypt, 'execute', [$inputInterface, $outputInterface]);
	}
}
