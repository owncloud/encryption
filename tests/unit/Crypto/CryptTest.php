<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
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

use OC\HintException;
use OCA\Encryption\Crypto\Crypt;
use Symfony\Component\EventDispatcher\GenericEvent;
use Test\TestCase;

class CryptTest extends TestCase {

	/** @var \OCP\ILogger|\PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	/** @var \OCP\IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	private $userSession;

	/** @var \OCP\IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;

	/** @var \OCP\IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l;

	/** @var Crypt */
	private $crypt;

	public function setUp() {
		parent::setUp();

		$this->logger = $this->getMockBuilder('OCP\ILogger')
			->disableOriginalConstructor()
			->getMock();
		$this->logger->expects($this->any())
			->method('warning')
			->willReturn(true);
		$this->userSession = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();
		$this->config = $this->getMockBuilder('OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$this->l = $this->createMock('OCP\IL10N');

		$this->crypt = new Crypt($this->logger, $this->userSession, $this->config, $this->l);
	}

	/**
	 * test getOpenSSLConfig without any additional parameters
	 */
	public function testGetOpenSSLConfigBasic() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with($this->equalTo('openssl'), $this->equalTo([]))
			->willReturn([]);

		$result = self::invokePrivate($this->crypt, 'getOpenSSLConfig');
		$this->assertSame(1, \count($result));
		$this->assertArrayHasKey('private_key_bits', $result);
		$this->assertSame(4096, $result['private_key_bits']);
	}

	/**
	 * test getOpenSSLConfig with additional parameters defined in config.php
	 */
	public function testGetOpenSSLConfig() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with($this->equalTo('openssl'), $this->equalTo([]))
			->willReturn(['foo' => 'bar', 'private_key_bits' => 1028]);

		$result = self::invokePrivate($this->crypt, 'getOpenSSLConfig');
		$this->assertSame(2, \count($result));
		$this->assertArrayHasKey('private_key_bits', $result);
		$this->assertArrayHasKey('foo', $result);
		$this->assertSame(1028, $result['private_key_bits']);
		$this->assertSame('bar', $result['foo']);
	}

	/**
	 * test generateHeader with valid key formats
	 *
	 * @dataProvider dataTestGenerateHeader
	 */
	public function testGenerateHeader($keyFormat, $expected) {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with($this->equalTo('cipher'), $this->equalTo('AES-256-CTR'))
			->willReturn('AES-128-CFB');

		if ($keyFormat) {
			$result = $this->crypt->generateHeader($keyFormat);
		} else {
			$result = $this->crypt->generateHeader();
		}

		$this->assertSame($expected, $result);
	}

	/**
	 * test generateHeader with invalid key format
	 *
	 * @expectedException \InvalidArgumentException
	 */
	public function testGenerateHeaderInvalid() {
		$this->crypt->generateHeader('unknown');
	}

	/**
	 * @return array
	 */
	public function dataTestGenerateHeader() {
		return [
			[null, 'HBEGIN:cipher:AES-128-CFB:keyFormat:hash:HEND'],
			['password', 'HBEGIN:cipher:AES-128-CFB:keyFormat:password:HEND'],
			['hash', 'HBEGIN:cipher:AES-128-CFB:keyFormat:hash:HEND']
		];
	}

	public function testGetCipherWithInvalidCipher() {
		$this->config->expects($this->once())
				->method('getSystemValue')
				->with($this->equalTo('cipher'), $this->equalTo('AES-256-CTR'))
				->willReturn('Not-Existing-Cipher');
		$this->logger
			->expects($this->once())
			->method('warning')
			->with('Unsupported cipher (Not-Existing-Cipher) defined in config.php supported. Falling back to AES-256-CTR');

		// Workaround for OpenSSL 0.9.8. Fallback to an old cipher that should work.
		if (OPENSSL_VERSION_NUMBER < 0x1000101f) {
			$this->assertSame('AES-128-CFB', $this->crypt->getCipher());
		} else {
			$this->assertSame('AES-256-CTR', $this->crypt->getCipher());
		}
	}

	/**
	 * @dataProvider dataProviderGetCipher
	 * @param string $configValue
	 * @param string $expected
	 */
	public function testGetCipher($configValue, $expected) {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with($this->equalTo('cipher'), $this->equalTo('AES-256-CTR'))
			->willReturn($configValue);

		if (OPENSSL_VERSION_NUMBER < 0x1000101f) {
			if ($configValue === 'AES-256-CTR' || $configValue === 'AES-128-CTR' || $configValue === 'unknown') {
				$expected = 'AES-128-CFB';
			}
		}

		$this->assertSame($expected, $this->crypt->getCipher());
	}

	/**
	 * data provider for testGetCipher
	 *
	 * @return array
	 */
	public function dataProviderGetCipher() {
		return [
			['AES-128-CFB', 'AES-128-CFB'],
			['AES-256-CFB', 'AES-256-CFB'],
			['AES-128-CTR', 'AES-128-CTR'],
			['AES-256-CTR', 'AES-256-CTR'],

			['unknown', 'AES-256-CTR']
		];
	}

	/**
	 * test concatIV()
	 */
	public function testConcatIV() {
		$result = self::invokePrivate(
			$this->crypt,
			'concatIV',
			['content', 'my_iv']);

		$this->assertSame('content00iv00my_iv',
			$result
		);
	}

	/**
	 * @dataProvider dataTestSplitMetaData
	 */
	public function testSplitMetaData($data, $expected) {
		$result = self::invokePrivate($this->crypt, 'splitMetaData', [$data, 'AES-256-CFB']);
		$this->assertTrue(\is_array($result));
		$this->assertSame(3, \count($result));
		$this->assertArrayHasKey('encrypted', $result);
		$this->assertArrayHasKey('iv', $result);
		$this->assertArrayHasKey('signature', $result);
		$this->assertSame($expected['encrypted'], $result['encrypted']);
		$this->assertSame($expected['iv'], $result['iv']);
		$this->assertSame($expected['signature'], $result['signature']);
	}

	public function dataTestSplitMetaData() {
		return [
			['encryptedContent00iv001234567890123456xx',
				['encrypted' => 'encryptedContent', 'iv' => '1234567890123456', 'signature' => false]],
			['encryptedContent00iv00123456789012345600sig00e1992521e437f6915f9173b190a512cfc38a00ac24502db44e0ba10c2bb0cc86xxx',
				['encrypted' => 'encryptedContent', 'iv' => '1234567890123456', 'signature' => 'e1992521e437f6915f9173b190a512cfc38a00ac24502db44e0ba10c2bb0cc86']],
		];
	}

	/**
	 * @dataProvider dataTestHasSignature
	 */
	public function testHasSignature($data, $expected) {
		$this->assertSame($expected,
			$this->invokePrivate($this->crypt, 'hasSignature', [$data, 'AES-256-CFB'])
		);
	}

	public function dataTestHasSignature() {
		return [
			['encryptedContent00iv001234567890123456xx', false],
			['encryptedContent00iv00123456789012345600sig00e1992521e437f6915f9173b190a512cfc38a00ac24502db44e0ba10c2bb0cc86xxx', true]
		];
	}

	/**
	 * @dataProvider dataTestHasSignatureFail
	 * @expectedException \OC\HintException
	 */
	public function testHasSignatureFail($cipher) {
		$data = 'encryptedContent00iv001234567890123456xx';
		$this->invokePrivate($this->crypt, 'hasSignature', [$data, $cipher]);
	}

	public function dataTestHasSignatureFail() {
		return [
			['AES-256-CTR'],
			['aes-256-ctr'],
			['AES-128-CTR'],
			['ctr-256-ctr']
		];
	}

	/**
	 * test addPadding()
	 */
	public function testAddPadding() {
		$result = self::invokePrivate($this->crypt, 'addPadding', ['data']);
		$this->assertSame('dataxxx', $result);
	}

	/**
	 * test removePadding()
	 *
	 * @dataProvider dataProviderRemovePadding
	 * @param $data
	 * @param $expected
	 */
	public function testRemovePadding($data, $expected) {
		$result = self::invokePrivate($this->crypt, 'removePadding', [$data]);
		$this->assertSame($expected, $result);
	}

	/**
	 * data provider for testRemovePadding
	 *
	 * @return array
	 */
	public function dataProviderRemovePadding() {
		return [
			['dataxx', 'data'],
			['data', false]
		];
	}

	/**
	 * test parseHeader()
	 */
	public function testParseHeader() {
		$header= 'HBEGIN:foo:bar:cipher:AES-256-CFB:HEND';
		$result = self::invokePrivate($this->crypt, 'parseHeader', [$header]);

		$this->assertTrue(\is_array($result));
		$this->assertSame(2, \count($result));
		$this->assertArrayHasKey('foo', $result);
		$this->assertArrayHasKey('cipher', $result);
		$this->assertSame('bar', $result['foo']);
		$this->assertSame('AES-256-CFB', $result['cipher']);
	}

	/**
	 * test encrypt()
	 *
	 * @return string
	 */
	public function testEncrypt() {
		$decrypted = 'content';
		$password = 'password';
		$iv = self::invokePrivate($this->crypt, 'generateIv');

		$this->assertTrue(\is_string($iv));
		$this->assertSame(16, \strlen($iv));

		$result = self::invokePrivate($this->crypt, 'encrypt', [$decrypted, $iv, $password]);

		$this->assertTrue(\is_string($result));

		return [
			'password' => $password,
			'iv' => $iv,
			'encrypted' => $result,
			'decrypted' => $decrypted];
	}

	/**
	 * test decrypt()
	 *
	 * @depends testEncrypt
	 */
	public function testDecrypt($data) {
		$result = self::invokePrivate(
			$this->crypt,
			'decrypt',
			[$data['encrypted'], $data['iv'], $data['password']]);

		$this->assertSame($data['decrypted'], $result);
	}

	/**
	 * test return values of valid ciphers
	 *
	 * @dataProvider dataTestGetKeySize
	 */
	public function testGetKeySize($cipher, $expected) {
		$result = $this->invokePrivate($this->crypt, 'getKeySize', [$cipher]);
		$this->assertSame($expected, $result);
	}

	/**
	 * test exception if cipher is unknown
	 *
	 * @expectedException \InvalidArgumentException
	 */
	public function testGetKeySizeFailure() {
		$this->invokePrivate($this->crypt, 'getKeySize', ['foo']);
	}

	/**
	 * @return array
	 */
	public function dataTestGetKeySize() {
		return [
			['AES-256-CFB', 32],
			['AES-128-CFB', 16],
			['AES-256-CTR', 32],
			['AES-128-CTR', 16],
		];
	}

	/**
	 * @dataProvider dataTestDecryptPrivateKey
	 */
	public function testDecryptPrivateKey($header, $privateKey, $expectedCipher, $isValidKey, $expected) {
		/** @var \OCA\Encryption\Crypto\Crypt | \PHPUnit_Framework_MockObject_MockObject $crypt */
		$crypt = $this->getMockBuilder('OCA\Encryption\Crypto\Crypt')
			->setConstructorArgs(
				[
					$this->logger,
					$this->userSession,
					$this->config,
					$this->l
				]
			)
			->setMethods(
				[
					'parseHeader',
					'generatePasswordHash',
					'symmetricDecryptFileContent',
					'isValidPrivateKey'
				]
			)
			->getMock();

		$crypt->expects($this->once())->method('parseHeader')->willReturn($header);
		if (isset($header['keyFormat']) && $header['keyFormat'] === 'hash') {
			$crypt->expects($this->once())->method('generatePasswordHash')->willReturn('hash');
			$password = 'hash';
		} else {
			$crypt->expects($this->never())->method('generatePasswordHash');
			$password = 'password';
		}

		$crypt->expects($this->once())->method('symmetricDecryptFileContent')
			->with('privateKey', $password, $expectedCipher)->willReturn('key');
		$crypt->expects($this->once())->method('isValidPrivateKey')->willReturn($isValidKey);

		$result = $crypt->decryptPrivateKey($privateKey, 'password');

		$this->assertSame($expected, $result);
	}

	/**
	 * @return array
	 */
	public function dataTestDecryptPrivateKey() {
		return [
			[['cipher' => 'AES-128-CFB', 'keyFormat' => 'password'], 'HBEGIN:HENDprivateKey', 'AES-128-CFB', true, 'key'],
			[['cipher' => 'AES-256-CFB', 'keyFormat' => 'password'], 'HBEGIN:HENDprivateKey', 'AES-256-CFB', true, 'key'],
			[['cipher' => 'AES-256-CFB', 'keyFormat' => 'password'], 'HBEGIN:HENDprivateKey', 'AES-256-CFB', false, false],
			[['cipher' => 'AES-256-CFB', 'keyFormat' => 'hash'], 'HBEGIN:HENDprivateKey', 'AES-256-CFB', true, 'key'],
			[['cipher' => 'AES-256-CFB'], 'HBEGIN:HENDprivateKey', 'AES-256-CFB', true, 'key'],
			[[], 'privateKey', 'AES-128-CFB', true, 'key'],
		];
	}

	public function testIsValidPrivateKey() {
		$res = \openssl_pkey_new();
		\openssl_pkey_export($res, $privateKey);

		// valid private key
		$this->assertTrue(
			$this->invokePrivate($this->crypt, 'isValidPrivateKey', [$privateKey])
		);

		// invalid private key
		$this->assertFalse(
			$this->invokePrivate($this->crypt, 'isValidPrivateKey', ['foo'])
		);
	}

	public function testsymmetricDecryptFileContentForHintException() {
		/** @var \OCA\Encryption\Crypto\Crypt | \PHPUnit_Framework_MockObject_MockObject $crypt */
		$crypt = $this->getMockBuilder('OCA\Encryption\Crypto\Crypt')
			->setConstructorArgs(
				[
					$this->logger,
					$this->userSession,
					$this->config,
					$this->l
				]
			)
			->setMethods(
				[
					'splitMetaData',
					'parseHeader',
					'generatePasswordHash',
					'checkSignature',
					'isValidPrivateKey',
					'setSignatureMismatch',
				]
			)
			->getMock();

		$crypt->method('splitMetaData')
			->willReturn(['signature' => true]);
		$crypt->method('checkSignature')
			->willThrowException(new HintException("Missing Signature"));

		$this->logger->expects($this->once())
			->method('logException');
		$crypt->expects($this->once())
			->method('setSignatureMismatch')
			->with(true);

		$crypt->symmetricDecryptFileContent("encryptedContent00iv00123456789012345600sig00e1992521e437f6915f9173b190a512cfc38a00ac24502db44e0ba10c2bb0cc86xxx", "test", "AES-256-CFB");
	}

	public function testsignatureMismatchEvent() {
		$genericEvent = new GenericEvent(null, []);
		$this->invokePrivate($this->crypt, 'setSignatureMismatch', [true]);
		$this->crypt->signatureMismatchEvent($genericEvent);
		$this->assertFalse($this->invokePrivate($this->crypt, 'signatureMismatchHappened', []));
	}
}
