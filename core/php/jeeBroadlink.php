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
require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
if (!jeedom::apiAccess(init('apikey'), 'broadlink')) {
	echo __("Vous n'êtes pas autorisé à effectuer cette action", __FILE__);
	die();
}

if (init('test') != '') {
	echo 'OK';
	die();
}
$result = json_decode(file_get_contents("php://input"), true);
if (!is_array($result)) {
	die();
}

if (isset($result['learn_mode'])) {
	if ($result['learn_mode'] == 1) {
		config::save('include_mode', 1, 'broadlink');
		event::add(
			'broadlink::includeState',
			array(
				'state' => 1
			)
		);
	} else {
		config::save('include_mode', 0, 'broadlink');
		event::add(
			'broadlink::includeState',
			array(
				'state' => 0
			)
		);
	}
	die();
}
if (isset($result['foundfrequency'])) {
	if ($result['foundfrequency'] == 1) {
		event::add(
			'broadlink::foundfrequency',
			array(
				'state' => 1
			)
		);
	} else {
		event::add(
			'broadlink::foundfrequency',
			array(
				'state' => 0
			)
		);
	}
	die();
}
if (isset($result['step2'])) {
	event::add(
		'broadlink::step2',
		array(
			'state' => 1
		)
	);
	die();
}
if (isset($result['devices'])) {
	foreach ($result['devices'] as $key => $datas) {
		if (!isset($datas['mac'])) {
			continue;
		}
		$logicalId = $key;
		$broadlink = broadlink::byLogicalId($logicalId, 'broadlink');
		if (isset($datas['reversemac'])) {
			$broadlink2 = broadlink::byLogicalId($datas['reversemac'], 'broadlink');
			if (!is_object($broadlink) && is_object($broadlink2)) {
				$broadlink = $broadlink2;
			}
		}
		if (!is_object($broadlink)) {
			if ($datas['learn'] != 1) {
				continue;
			}
			$broadlink = broadlink::createFromDef($datas);
			if (!is_object($broadlink)) {
				log::add('broadlink', 'debug', __('Aucun équipement trouvé pour', __FILE__) . ' : ' . secureXSS($datas['id']));
				continue;
			}
			event::add('jeedom::alert', array(
				'level' => 'warning',
				'page' => 'broadlink',
				'message' => '',
			));
			event::add('broadlink::includeDevice', $broadlink->getId());
		}
		if (!$broadlink->getIsEnable()) {
			continue;
		}
		if (isset($datas['learnedCmd']) && $datas['learnedCmd'] == 1) {
			if ($datas['hexcode'] == 'no') {
				event::add('broadlink::missedCommand', $broadlink->getId());
				continue;
			}
			$number = count($broadlink->getCmd()) + 1;
			$cmd = $broadlink->getCmd(null, $number . substr($datas['hexcode'], 0, 50));
			if (!is_object($cmd)) {
				$cmd = new broadlinkCmd();
				$cmd->setLogicalId($number . substr($datas['hexcode'], 0, 50));
				$cmd->setIsVisible(1);
				$cmd->setName($number . __('Commande', __FILE__) . substr($datas['hexcode'], 0, 10));
			}
			$cmd->setType('action');
			$cmd->setSubType('other');
			$cmd->setconfiguration('logicalid', 'hex2send:' . $datas['hexcode']);
			$cmd->setEqLogic_id($broadlink->getId());
			$cmd->save();
			event::add('broadlink::includeCommand', $broadlink->getId());
			continue;
		}
		foreach ($broadlink->getCmd('info') as $cmd) {
			$logicalId = $cmd->getConfiguration('logicalid');
			if ($logicalId == '') {
				continue;
			}
			$path = explode('::', $logicalId);
			$value = $datas;
			foreach ($path as $key) {
				if (!isset($value[$key])) {
					continue (2);
				}
				$value = $value[$key];
				if (!is_array($value) && strpos($value, 'toggle') !== false && $cmd->getSubType() == 'binary') {
					$value = $cmd->execCmd();
					$value = ($value != 0) ? 0 : 1;
				}
				if ($key == 'battery') {
					log::add('broadlink', 'debug', $value);
					$value = ($value != 0) ? 0 : 100;
					$broadlink->batteryStatus($value);
				}
			}
			if (!is_array($value)) {
				$cmd->event($value);
			}
		}
	}
}
