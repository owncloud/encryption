<?php
/**
 * @author Sujith Haridasan <sharidasan@owncloud.com>
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

use OCA\Encryption\Command\HSMDaemon;
use OCP\IConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class HSMDaemonTest extends TestCase {
	/** @var IConfig | \PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var HSMDaemon */
	private $hsmDeamon;

	public function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->hsmDeamon = new HSMDaemon($this->config);
	}

	public function testExecuteWithExportMasterKey() {
		$inputInterface = $this->createMock(InputInterface::class);
		$inputInterface->method('getOption')
			->willReturnMap([
				['decrypt', false],
				['export-masterkey', true]
			]);

		$outputInterface = $this->createMock(OutputInterface::class);
		$outputInterface->expects($this->once())->method('writeln')
			->with("current masterkey (base64 encoded): ''");

		$this->config->method('getAppValue')
			->willReturnMap([
				['encryption', 'hsm.url', '', 'http://localhost:1234'],
				['encryption', 'masterKeyId', '', 'abcd']
			]);

		$this->invokePrivate($this->hsmDeamon, 'execute', [$inputInterface, $outputInterface]);
	}

	public function testExecuteFailsNoHSMURLSet() {
		$inputInterface = $this->createMock(InputInterface::class);
		$inputInterface->method('getOption')
			->willReturn(null);

		$outputInterface = $this->createMock(OutputInterface::class);
		$outputInterface->expects($this->once())
			->method('writeln')
			->with("<error>hsm.url not set</error>");

		$this->invokePrivate($this->hsmDeamon, 'execute', [$inputInterface, $outputInterface]);
	}
}
