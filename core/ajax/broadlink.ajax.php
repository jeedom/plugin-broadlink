<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

try {
	require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
	include_file('core', 'authentification', 'php');

	if (!isConnect('admin')) {
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}

	ajax::init();

	if (init('action') == 'changeIncludeState') {
		broadlink::changeIncludeState(init('state'));
		ajax::success();
	}

	if (init('action') == 'getModelListParam') {
		/** @var broadlink */
		$broadlink = broadlink::byId(init('id'));
		if (!is_object($broadlink)) {
			ajax::success(array());
		}
		ajax::success($broadlink->getModelListParam(init('conf')));
	}

	if (init('action') == 'learn') {
		/** @var broadlink */
		$broadlink = broadlink::byId(init('id'));
		if (!is_object($broadlink)) {
			ajax::success(array());
		}
		ajax::success($broadlink->learn(init('mode')));
	}

	if (init('action') == 'synchronise') {
		/** @var broadlink */
		$broadlink = broadlink::byLogicalId(init('id'), 'broadlink');
		if (!is_object($broadlink)) {
			ajax::success(array());
		}
		ajax::success($broadlink->synchronise(init('commands'), init('targets')));
	}

	throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
	/*     * *********Catch exeption*************** */
} catch (Exception $e) {
	ajax::error(displayException($e), $e->getCode());
}
