<?php
/**
* @author Sujith Haridasan <sharidasan@owncloud.com>
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


namespace OCA\Encryption\Command;

use OC\Encryption\CustomEncryptionWrapper;
use OC\Encryption\Exceptions\DecryptionFailedException;
use OC\Encryption\Manager;
use OC\Files\Filesystem;
use OC\Files\View;
use OC\Memcache\ArrayCache;
use OCA\Encryption\Crypto\EncryptAll;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Users\Setup;
use OCA\Encryption\Util;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\ISession;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Security\ISecureRandom;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class RecreateMasterKey extends Command {

	/** @var Manager  */
	protected $encryptionManager;

	/** @var IUserManager  */
	protected $userManager;

	/** @var View  */
	protected $rootView;

	/** @var KeyManager  */
	protected $keyManager;

	/** @var Util  */
	protected $util;

	/** @var  IAppManager */
	protected $IAppManager;

	/** @var  IAppConfig */
	protected $appConfig;

	/** @var IConfig  */
	protected $config;

	/** @var ISession  */
	protected $session;

	/** @var QuestionHelper  */
	protected $questionHelper;

	/** @var Setup  */
	protected $userSetup;

	/** @var IMailer  */
	protected $mailer;

	/** @var ISecureRandom  */
	protected $secureRandom;

	/** @var IL10N  */
	protected $l;

	/** @var ILogger  */
	protected $logger;

	/** @var  */
	protected $encryptAll;

	/** @var array files which couldn't be decrypted */
	protected $failed;

	/**
	 * RecreateMasterKey constructor.
	 *
	 * @param IUserManager $userManager
	 * @param View $rootView
	 * @param KeyManager $keyManager
	 * @param Util $util
	 * @param IAppManager $IAppManager
	 * @param IAppConfig $appConfig
	 * @param IConfig $config
	 * @param ISession $session
	 * @param QuestionHelper $questionHelper
	 * @param Setup $userSetup
	 * @param IMailer $mailer
	 * @param ISecureRandom $secureRandom
	 * @param IL10N $l
	 * @param ILogger $logger
	 */
	public function __construct(IUserManager $userManager, View $rootView, KeyManager $keyManager, Util $util,
								IAppManager $IAppManager, IAppConfig $appConfig, IConfig $config, ISession $session,
								QuestionHelper $questionHelper, Setup $userSetup, IMailer $mailer,
								ISecureRandom $secureRandom, IL10N $l, ILogger $logger) {

		parent::__construct();
		$this->userManager = $userManager;
		$this->rootView = $rootView;
		$this->keyManager = $keyManager;
		$this->util = $util;
		$this->IAppManager = $IAppManager;
		$this->appConfig = $appConfig;
		$this->config = $config;
		$this->session = $session;
		$this->questionHelper = $questionHelper;
		$this->userSetup = $userSetup;
		$this->mailer = $mailer;
		$this->secureRandom = $secureRandom;
		$this->l = $l;
		$this->logger = $logger;
	}

	protected function configure() {
		parent::configure();

		$this
			->setName('encryption:recreate-master-key')
			->setDescription('Replace existing master key with new one. Encrypt the file system with newly created master key')
		;

		$this->addOption(
			'yes',
			'y',
			InputOption::VALUE_NONE,
			'Answer yes to all questions'
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$yes = $input->getOption('yes');
		if ($this->util->isMasterKeyEnabled()) {
			$question = new ConfirmationQuestion(
				'Warning: Inorder to re-create master key, the entire ownCloud filesystem will be decrypted and then encrypted using new master key.'
				. ' Do you want to continue? (y/n)', false);
			if ($yes || $this->questionHelper->ask($input, $output, $question)) {
				$this->addCustomWrapper();

				$output->writeln("Decryption started\n");
				$progress = new ProgressBar($output);
				$progress->start();
				$progress->setMessage("Decryption progress...");
				$progress->advance();

				$this->decryptAllUsersFiles($progress, $output);
				$progress->finish();

				if (empty($this->failed)) {

					$this->IAppManager->disableApp('encryption');

					//Delete the files_encryption dir
					$this->rootView->deleteAll('files_encryption');

					$this->appConfig->setValue('core', 'encryption_enabled', 'no');
					$this->appConfig->deleteKey('encryption', 'useMasterKey');
					$this->appConfig->deleteKey('encryption', 'masterKeyId');
					$this->appConfig->deleteKey('encryption', 'recoveryKeyId');
					$this->appConfig->deleteKey('encryption', 'publicShareKeyId');
					$this->appConfig->deleteKey('files_encryption', 'installed_version');

				}
				$output->writeln("\nDecryption completed\n");

				$this->removeCustomWrapper();

				//Reencrypt again
				$this->IAppManager->enableApp('encryption');
				$this->appConfig->setValue('core', 'encryption_enabled', 'yes');
				$this->appConfig->setValue('encryption', 'enabled', 'yes');
				$output->writeln("Encryption started\n");

				$output->writeln("Waiting for creating new masterkey\n");

				$this->keyManager->setPublicShareKeyIDAndMasterKeyId();

				$output->writeln("New masterkey created successfully\n");

				$this->appConfig->setValue('encryption', 'enabled', 'yes');
				$this->appConfig->setValue('encryption', 'useMasterKey', '1');

				$this->keyManager->validateShareKey();
				$this->keyManager->validateMasterKey();
				$this->encryptAllUsers($input, $output);
				$output->writeln("\nEncryption completed successfully\n");
			} else {
				$output->writeln("The process is abandoned");
			}
		} else {
			$output->writeln("Master key is not enabled.\n");
		}
	}

	protected function addCustomWrapper() {
		$customEncryptionWrapper = new CustomEncryptionWrapper(new ArrayCache(), \OC::$server->getEncryptionManager(), $this->logger);
		Filesystem::addStorageWrapper('oc_customencryption', [$customEncryptionWrapper, 'wrapCustomStorage'], 1);
	}

	protected function removeCustomWrapper() {
		\OC\Files\Filesystem::getLoader()->removeStorageWrapper('oc_customencryption');
	}

	protected function encryptAllUsers(InputInterface $input, OutputInterface $output) {
		/*
		 * We are reusing the encryptAll code but not the decryptAll. The reason being
		 * decryptAll finishes by encrypting. Which is not what we want. This will make
		 * things out of scope for this command. We want first the entire oC FS to be
		 * decrypt. Then re-encrypt the entire oC FS with the new master key generated.
		 *
		 */
		$this->encryptAll = new EncryptAll(
			$this->userSetup, $this->userManager, $this->rootView,
			$this->keyManager, $this->util, $this->config,
			$this->mailer, $this->l, $this->questionHelper,
			$this->secureRandom);
		$this->encryptAll->encryptAll($input, $output);
	}

	protected function decryptAllUsersFiles(ProgressBar $progress, OutputInterface $output) {
		$userList = [];

		foreach ($this->userManager->getBackends() as $backend) {
			$limit = 500;
			$offset = 0;
			do {
				$users = $backend->getUsers('', $limit, $offset);
				foreach ($users as $user) {
					$userList[] = $user;
				}
				$offset += $limit;
			} while (count($users) >= $limit);
		}

		$userNo = 1;
		foreach ($userList as $uid) {
			$this->decryptUsersFiles($uid, $progress, $output);
			$progress->advance();
			$userNo++;
		}
		return true;
	}

	protected function decryptUsersFiles($uid, ProgressBar $progress, OutputInterface $output) {
		$this->setupUserFS($uid);
		$directories = [];
		$directories[] = '/' . $uid . '/files';

		while ($root = array_pop($directories)) {
			$content = $this->rootView->getDirectoryContent($root);
			foreach ($content as $file) {
				// only decrypt files owned by the user
				if($file->getStorage()->instanceOfStorage('OCA\Files_Sharing\SharedStorage')) {
					continue;
				}
				$path = $root . '/' . $file['name'];
				if ($this->rootView->is_dir($path)) {
					$directories[] = $path;
					continue;
				} else {
					try {
						if ($file->isEncrypted() !== false) {
							if ($this->decryptFile($path) === false) {
								$progress->setMessage("decrypt files for user $uid: $path (already decrypted)");
								$progress->advance();
							}
						}
					} catch (\Exception $e) {
						if (isset($this->failed[$uid])) {
							$this->failed[$uid][] = $path;
						} else {
							$this->failed[$uid] = [$path];
						}
					}
				}
			}
		}

		if (empty($this->failed)) {
			$this->rootView->deleteAll("$uid/files_encryption");
		} else {
			$output->writeln('Files for following users couldn\'t be decrypted, ');
			$output->writeln('maybe the user is not set up in a way that supports this operation: ');
			foreach ($this->failed as $uid => $paths) {
				$output->writeln('    ' . $uid);
			}
			$output->writeln('');
		}
	}

	protected function decryptFile($path) {

		$source = $path;
		$target = $path . '.decrypted.' . $this->getTimestamp();

		try {
			$this->rootView->copy($source, $target,false, true);
			$this->rootView->rename($target, $source);
			$this->keyManager->setVersion($source,0, $this->rootView);
		} catch (DecryptionFailedException $e) {
			if ($this->rootView->file_exists($target)) {
				$this->rootView->unlink($target);
			}
			return false;
		}

		return true;
	}

	protected function getTimestamp() {
		return time();
	}

	protected function setupUserFS($uid) {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($uid);
	}
}
