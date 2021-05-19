<?php
/**
 * @author Sujith Haridasan <sharidasan@owncloud.com>
 * @author Ilja Neumann <ineumann@owncloud.com>
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

namespace OCA\Encryption\Command;

use OC\Files\View;
use OC\HintException;
use OCP\Files\IRootFolder;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixEncryptedVersion extends Command {
	/** @var IRootFolder  */
	private $rootFolder;

	/** @var IUserManager  */
	private $userManager;

	/** @var View  */
	private $view;

	public function __construct(IRootFolder $rootFolder, IUserManager $userManager, View $view) {
		$this->rootFolder = $rootFolder;
		$this->userManager = $userManager;
		$this->view = $view;
		parent::__construct();
	}

	protected function configure() {
		parent::configure();

		$this
			->setName('encryption:fix-encrypted-version')
			->setDescription('Fix the encrypted version if the encrypted file(s) are not downloadable.')
			->addArgument(
				'user',
				InputArgument::REQUIRED,
				'The id of the user whose files need fixing'
			)->addOption(
				'path',
				'p',
				InputArgument::OPTIONAL,
				'Limit files to fix with path, e.g., --path="/Music/Artist". If path indicates a directory, all the files inside directory will be fixed.'
			);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$user = $input->getArgument('user');
		$pathToWalk = "/$user/files";

		/**
		 * trim() returns an empty string when the argument is an unset/null
		 */
		$pathOption = \trim($input->getOption('path'), '/');
		if ($pathOption !== "") {
			$pathToWalk = "$pathToWalk/$pathOption";
		}

		if ($user === null) {
			$output->writeln("<error>No user id provided.</error>\n");
			return 1;
		}

		if ($this->userManager->get($user) === null) {
			$output->writeln("<error>User id $user does not exist. Please provide a valid user id</error>");
			return 1;
		}
		return $this->walkPathOfUser($user, $pathToWalk, $output);
	}

	/**
	 * @param string $user
	 * @param string $path
	 * @param OutputInterface $output
	 * @return int 0 for success, 1 for error
	 */
	private function walkPathOfUser($user, $path, OutputInterface $output) {
		$this->setupUserFs($user);
		if (!$this->view->file_exists($path)) {
			$output->writeln("<error>Path $path does not exist. Please provide a valid path.</error>");
			return 1;
		}

		if ($this->view->is_file($path)) {
			$output->writeln("Verifying the content of file $path");
			$this->verifyFileContent($path, $output);
			return 0;
		}
		$directories = [];
		$directories[] = $path;
		while ($root = \array_pop($directories)) {
			$directoryContent = $this->view->getDirectoryContent($root);
			foreach ($directoryContent as $file) {
				$path = $root . '/' . $file['name'];
				if ($this->view->is_dir($path)) {
					$directories[] = $path;
				} else {
					$output->writeln("Verifying the content of file $path");
					$this->verifyFileContent($path, $output);
				}
			}
		}
		return 0;
	}

	/**
	 * @param string $path
	 * @param OutputInterface $output
	 * @param bool $ignoreCorrectEncVersionCall, setting this variable to false avoids recursion
	 */
	private function verifyFileContent($path, OutputInterface $output, $ignoreCorrectEncVersionCall = true) {
		try {
			/**
			 * In encryption, the files are read in a block size of 8192 bytes
			 * Read block size of 8192 and a bit more (808 bytes)
			 * If there is any problem, the first block should throw the signature
			 * mismatch error. Which as of now, is enough to proceed ahead to
			 * correct the encrypted version.
			 */
			$handle = $this->view->fopen($path, 'rb');

			if (\fread($handle, 9001) !== false) {
				$output->writeln("<info>The file $path is: OK</info>");
			}

			\fclose($handle);

			return true;
		} catch (HintException $e) {
			\OC::$server->getLogger()->warning("Issue: " . $e->getMessage());
			//If allowOnce is set to false, this becomes recursive.
			if ($ignoreCorrectEncVersionCall === true) {
				//Lets rectify the file by correcting encrypted version
				$output->writeln("<info>Attempting to fix the path: $path</info>");
				return $this->correctEncryptedVersion($path, $output);
			}
			return false;
		}
	}

	/**
	 * @param string $path
	 * @param OutputInterface $output
	 * @return bool
	 */
	private function correctEncryptedVersion($path, OutputInterface $output) {
		$fileInfo = $this->view->getFileInfo($path);
		$fileId = $fileInfo->getId();
		$encryptedVersion = $fileInfo->getEncryptedVersion();
		$wrongEncryptedVersion = $encryptedVersion;

		$storage = $fileInfo->getStorage();

		$cache = $storage->getCache();
		$fileCache = $cache->get($fileId);

		if ($storage->instanceOfStorage('OCA\Files_Sharing\ISharedStorage')) {
			$output->writeln("<info>The file: $path is a share. Hence kindly fix this by running the script for the owner of share</info>");
			return true;
		}

		// Save original encrypted version so we can restore it if decryption fails with all version
		$originalEncryptedVersion = $encryptedVersion;
		if ($encryptedVersion >= 0) {
			//test by decrementing the value till 1 and if nothing works try incrementing
			$encryptedVersion--;
			while ($encryptedVersion > 0) {
				$cacheInfo = ['encryptedVersion' => $encryptedVersion, 'encrypted' => $encryptedVersion];
				$cache->put($fileCache->getPath(), $cacheInfo);
				$output->writeln("<info>Decrement the encrypted version to $encryptedVersion</info>");
				if ($this->verifyFileContent($path, $output, false) === true) {
					$output->writeln("<info>Fixed the file: $path with version " . $encryptedVersion . "</info>");
					return true;
				}
				$encryptedVersion--;
			}

			//So decrementing did not work. Now lets increment. Max increment is till 5
			$increment = 1;
			while ($increment <= 5) {
				/**
				 * The wrongEncryptedVersion would not be incremented so nothing to worry about here.
				 * Only the newEncryptedVersion is incremented.
				 * For example if the wrong encrypted version is 4 then
				 * cycle1 -> newEncryptedVersion = 5 ( 4 + 1)
				 * cycle2 -> newEncryptedVersion = 6 ( 4 + 2)
				 * cycle3 -> newEncryptedVersion = 7 ( 4 + 3)
				 */
				$newEncryptedVersion = $wrongEncryptedVersion + $increment;

				$cacheInfo = ['encryptedVersion' => $newEncryptedVersion, 'encrypted' => $newEncryptedVersion];
				$cache->put($fileCache->getPath(), $cacheInfo);
				$output->writeln("<info>Increment the encrypted version to $newEncryptedVersion</info>");
				if ($this->verifyFileContent($path, $output, false) === true) {
					$output->writeln("<info>Fixed the file: $path with version " . $newEncryptedVersion . "</info>");
					return true;
				}
				$increment++;
			}
		}

		$cacheInfo = ['encryptedVersion' => $originalEncryptedVersion, 'encrypted' => $originalEncryptedVersion];
		$cache->put($fileCache->getPath(), $cacheInfo);
		$output->writeln("<info>No fix found for $path, restored version to original: $originalEncryptedVersion</info>");

		return false;
	}

	/**
	 * Setup user file system
	 * @param string $uid
	 */
	private function setupUserFs($uid) {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($uid);
	}
}
