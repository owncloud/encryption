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


namespace OCA\Encryption;

use OCP\IL10N;
use OCP\Settings\ISection;

class AdminSection implements ISection {
	protected $l;

	public function __construct(IL10N $l) {
		$this->l = $l;
	}

	public function getPriority() {
		return 85;
	}

	public function getIconName() {
		return 'password';
	}

	public function getID() {
		return 'encryption';
	}

	public function getName() {
		return $this->l->t('Encryption');
	}
}

