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

/* * ***************************Includes********************************* */

class broadlink extends eqLogic {
	/*     * ***********************Methode static*************************** */

	public static function createFromDef($_def) {
		event::add('jeedom::alert', array(
			'level' => 'warning',
			'page' => 'broadlink',
			'message' => __('Nouveau module detecté', __FILE__),
		));
		$banId = explode(' ', config::byKey('banId', 'broadlink'));
		if (in_array($_def['mac'], $banId)) {
			event::add('jeedom::alert', array(
				'level' => 'danger',
				'page' => 'broadlink',
				'message' => __('Le module a un id banni. Inclusion impossible', __FILE__),
			));
			return false;
		}
		if (!isset($_def['mac']) || !isset($_def['type'])) {
			log::add('broadlink', 'error', 'Information manquante pour ajouter l\'équipement : ' . print_r($_def, true));
			event::add('jeedom::alert', array(
				'level' => 'danger',
				'page' => 'broadlink',
				'message' => __('Information manquante pour ajouter l\'équipement. Inclusion impossible', __FILE__),
			));
			return false;
		}
		$device = self::devicesParameters($_def['type']);
		$logicalId = $_def['mac'];
		if (isset($_def['data']['mac'])) {
			$logicalId = $_def['data']['mac'];
		}
		$broadlink = broadlink::byLogicalId($logicalId, 'broadlink');
		if (!is_object($broadlink)) {
			$eqLogic = new broadlink();
			$eqLogic->setName($logicalId);
		}
		$eqLogic->setLogicalId($logicalId);
		$eqLogic->setEqType_name('broadlink');
		$eqLogic->setIsEnable(1);
		$eqLogic->setIsVisible(1);
		$eqLogic->setConfiguration('device', strtolower($_def['type']));
		$eqLogic->setConfiguration('ip', $_def['ip']);
		$eqLogic->setConfiguration('port', $_def['port']);
		$model = $eqLogic->getModelListParam();
		if (count($model) > 0) {
			$eqLogic->setConfiguration('iconModel', array_keys($model[0])[0]);
		}
		$eqLogic->save();

		event::add('jeedom::alert', array(
			'level' => 'warning',
			'page' => 'broadlink',
			'message' => __('Module inclu avec succès', __FILE__),
		));
		return $eqLogic;
	}

	public static function devicesParameters($_device = '') {
		$return = array();
		foreach (ls(dirname(__FILE__) . '/../config/devices', '*') as $dir) {
			$path = dirname(__FILE__) . '/../config/devices/' . $dir;
			if (!is_dir($path)) {
				continue;
			}
			$files = ls($path, '*.json', false, array('files', 'quiet'));
			foreach ($files as $file) {
				try {
					$content = file_get_contents($path . '/' . $file);
					if (is_json($content)) {
						$return += json_decode($content, true);
					}
				} catch (Exception $e) {

				}
			}
		}
		if (isset($_device) && $_device != '') {
			if (isset($return[$_device])) {
				return $return[$_device];
			}
			return array();
		}
		return $return;
	}

	public static function deamon_info() {
		$return = array();
		$return['log'] = 'broadlink';
		$return['state'] = 'nok';
		$pid_file = jeedom::getTmpFolder('broadlink') . '/deamon.pid';
		if (file_exists($pid_file)) {
			if (@posix_getsid(trim(file_get_contents($pid_file)))) {
				$return['state'] = 'ok';
			} else {
				shell_exec('sudo rm -rf ' . $pid_file . ' 2>&1 > /dev/null;rm -rf ' . $pid_file . ' 2>&1 > /dev/null;');
			}
		}
		$return['launchable'] = 'ok';
		return $return;
	}

	public static function dependancy_info() {
		$return = array();
		$return['progress_file'] = '/tmp/dependancy_broadlink_in_progress';
		$return['state'] = 'ok';
		if (exec('sudo pip3 list | grep -E "pycrypto" | wc -l') < 1) {
			$return['state'] = 'nok';
		}
		return $return;
	}

	public static function dependancy_install() {
		log::remove(__CLASS__ . '_update');
		return array('script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder('broadlink') . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_update'));
	}

	public static function deamon_start() {
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		$broadlink_path = realpath(dirname(__FILE__) . '/../../resources/broadlinkd');
		$cmd = 'sudo /usr/bin/python3 ' . $broadlink_path . '/broadlinkd.py';
		$cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel('broadlink'));
		$cmd .= ' --socketport ' . config::byKey('socketport', 'broadlink');
		$cmd .= ' --sockethost 127.0.0.1';
		$cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/broadlink/core/php/jeeBroadlink.php';
		$cmd .= ' --apikey ' . jeedom::getApiKey('broadlink');
		$cmd .= ' --cycle ' . config::byKey('cycle', 'broadlink');
		$cmd .= ' --pid ' . jeedom::getTmpFolder('broadlink') . '/deamon.pid';
		log::add('broadlink', 'info', 'Lancement démon broadlink : ' . $cmd);
		$result = exec($cmd . ' >> ' . log::getPathToLog('broadlink') . ' 2>&1 &');
		$i = 0;
		while ($i < 30) {
			$deamon_info = self::deamon_info();
			if ($deamon_info['state'] == 'ok') {
				break;
			}
			sleep(1);
			$i++;
		}
		if ($i >= 30) {
			log::add('broadlink', 'error', 'Impossible de lancer le démon broadlink, vérifiez la log', 'unableStartDeamon');
			return false;
		}
		message::removeAll('broadlink', 'unableStartDeamon');
		sleep(2);
		self::sendIdToDeamon();
		config::save('include_mode', 0, 'broadlink');
		return true;
	}

	public static function sendIdToDeamon() {
		foreach (self::byType('broadlink') as $eqLogic) {
			$eqLogic->allowDevice();
			usleep(500);
		}
	}

	public static function deamon_stop() {
		$pid_file = jeedom::getTmpFolder('broadlink') . '/deamon.pid';
		if (file_exists($pid_file)) {
			$pid = intval(trim(file_get_contents($pid_file)));
			system::kill($pid);
		}
		system::kill('broadlinkd.py');
		system::fuserk(config::byKey('socketport', 'broadlink'));
		config::save('include_mode', 0, 'broadlink');
		sleep(1);
	}

	public static function changeIncludeState($_state) {
		if ($_state == 1) {
			$value = json_encode(array('apikey' => jeedom::getApiKey('broadlink'), 'cmd' => 'learnin'));
		} else {
			$value = json_encode(array('apikey' => jeedom::getApiKey('broadlink'), 'cmd' => 'learnout'));
		}
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
		socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'broadlink'));
		socket_write($socket, $value, strlen($value));
		socket_close($socket);
	}

/*     * *********************Methode d'instance************************* */
	public function getModelListParam($_conf = '') {
		if ($_conf == '') {
			$_conf = $this->getConfiguration('device');
		}
		$modelList = array();
		$user = false;
		$files = array();
		foreach (ls(dirname(__FILE__) . '/../config/devices', '*') as $dir) {
			if (!is_dir(dirname(__FILE__) . '/../config/devices/' . $dir)) {
				continue;
			}
			$files[$dir] = ls(dirname(__FILE__) . '/../config/devices/' . $dir, $_conf . '_*.png', false, array('files', 'quiet'));
			if (file_exists(dirname(__FILE__) . '/../config/devices/' . $dir . $_conf . '.png')) {
				$selected = 0;
				if ($dir . $_conf == $this->getConfiguration('iconModel')) {
					$selected = 1;
				}
				$modelList[$dir . $_conf] = array(
					'value' => __('Défaut', __FILE__),
					'selected' => $selected,
				);
			}
			if (count($files[$dir]) == 0) {
				unset($files[$dir]);
			}
		}
		$replace = array(
			$_conf => '',
			'.png' => '',
			'_' => ' ',
		);
		foreach ($files as $dir => $images) {
			foreach ($images as $imgname) {
				$selected = 0;
				if ($dir . str_replace('.png', '', $imgname) == $this->getConfiguration('iconModel')) {
					$selected = 1;
				}
				$modelList[$dir . str_replace('.png', '', $imgname)] = array(
					'value' => ucfirst(trim(str_replace(array_keys($replace), $replace, $imgname))),
					'selected' => $selected,
				);
			}
		}
		$canlearn = false;
		if ($this->getConfiguration('canlearn', 0) != 0) {
			$canlearn = true;
		}
		return [$modelList, $canlearn];
	}

	public function postSave() {
		if ($this->getConfiguration('applyDevice') != $this->getConfiguration('device')) {
			$this->applyModuleConfiguration();
		} else {
			$this->allowDevice();
		}
	}

	public function preRemove() {
		$this->disallowDevice();
	}
	
	public function preUpdate() {
		if (!preg_match('/.*?(-sub[0-9]*)$/', $this->getLogicalId()) && $this->getConfiguration('ischild',0) == 1) {
			$this->setLogicalId($this->getLogicalId().'-sub');
		}
	}

	public function allowDevice() {
		$value = array('apikey' => jeedom::getApiKey('broadlink'), 'cmd' => 'add');
		if ($this->getConfiguration('device') != '') {
			$value['device'] = array(
				'mac' => $this->getLogicalId(),
				'ip' => $this->getConfiguration('ip'),
				'name' => $this->getName(),
				'delay' => $this->getConfiguration('delay', 0),
				'port' => $this->getConfiguration('port'),
				'type' => $this->getConfiguration('device'),
			);
			$value = json_encode($value);
			$socket = socket_create(AF_INET, SOCK_STREAM, 0);
			socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'broadlink'));
			socket_write($socket, $value, strlen($value));
			socket_close($socket);
		}
	}

	public function disallowDevice() {
		if ($this->getLogicalId() == '') {
			return;
		}
		$value = json_encode(array('apikey' => jeedom::getApiKey('broadlink'), 'cmd' => 'remove', 'device' => array('mac' => $this->getLogicalId())));
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
		socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'broadlink'));
		socket_write($socket, $value, strlen($value));
		socket_close($socket);
	}

	public function learn($_mode) {
		$value = array('apikey' => jeedom::getApiKey('broadlink'), 'cmd' => 'send', 'cmdType' => 'learn');
		if ($this->getConfiguration('device') != '') {
			$value['device'] = array(
				'mac' => $this->getLogicalId(),
				'ip' => $this->getConfiguration('ip'),
				'name' => $this->getName(),
				'delay' => $this->getConfiguration('delay'),
				'port' => $this->getConfiguration('port'),
				'type' => $this->getConfiguration('device'),
				'mode' => $_mode,
			);
			$value = json_encode($value);
			$socket = socket_create(AF_INET, SOCK_STREAM, 0);
			socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'broadlink'));
			socket_write($socket, $value, strlen($value));
			socket_close($socket);
		}
	}

	public function synchronise($_commands, $_targets) {
		$commands = json_decode($_commands, true);
		$targets = json_decode($_targets, true);
		foreach ($targets as $targetid) {
			$target = broadlink::byId($targetid);
			foreach ($commands as $commandid) {
				$command = cmd::byId($commandid);
				foreach ($target->getCmd('action') as $targetcmd) {
					if ($targetcmd->getName() == $command->getName()) {
						$targetcmd->remove();
					}
				}
				$newCmd = new broadlinkCmd();
				$newCmd->setEqLogic_id($target->getId());
				$newCmd->setEqType('broadlink');
				$newCmd->setLogicalId($command->getLogicalId());
				$newCmd->setName($command->getName());
				$newCmd->setConfiguration('logicalid', $command->getConfiguration('logicalid'));
				$newCmd->setDisplay('icon', $command->getDisplay('icon'));
				$newCmd->setType('action');
				$newCmd->setSubType('other');
				$newCmd->save();
			}
		}
	}

	public function applyModuleConfiguration() {
		$this->setConfiguration('applyDevice', $this->getConfiguration('device'));
		$this->save();
		if ($this->getConfiguration('device') == '') {
			return true;
		}
		$device = self::devicesParameters($this->getConfiguration('device'));
		if (!is_array($device)) {
			return true;
		}
		event::add('jeedom::alert', array(
			'level' => 'warning',
			'page' => 'broadlink',
			'message' => __('Périphérique reconnu, intégration en cours', __FILE__),
		));

		if (isset($device['configuration'])) {
			foreach ($device['configuration'] as $key => $value) {
				$this->setConfiguration($key, $value);
			}
		}
		if (isset($device['category'])) {
			foreach ($device['category'] as $key => $value) {
				$this->setCategory($key, $value);
			}
		}
		$cmd_order = 0;
		$link_cmds = array();
		$link_actions = array();
		event::add('jeedom::alert', array(
			'level' => 'warning',
			'page' => 'broadlink',
			'message' => __('Création des commandes', __FILE__),
		));

		$ids = array();
		$arrayToRemove = [];
		if (isset($device['commands'])) {
			foreach ($this->getCmd() as $eqLogic_cmd) {
				$exists = 0;
				foreach ($device['commands'] as $command) {
					if ($command['logicalId'] == $eqLogic_cmd->getLogicalId()) {
						$exists++;
					}
				}
				if ($exists < 1) {
					$arrayToRemove[] = $eqLogic_cmd;
				}
			}
			foreach ($arrayToRemove as $cmdToRemove) {
				try {
					$cmdToRemove->remove();
				} catch (Exception $e) {

				}
			}
			foreach ($device['commands'] as $command) {
				$cmd = null;
				foreach ($this->getCmd() as $liste_cmd) {
					if ((isset($command['logicalId']) && $liste_cmd->getLogicalId() == $command['logicalId'])
						|| (isset($command['name']) && $liste_cmd->getName() == $command['name'])) {
						$cmd = $liste_cmd;
						break;
					}
				}
				try {
					if ($cmd == null || !is_object($cmd)) {
						$cmd = new broadlinkCmd();
						$cmd->setOrder($cmd_order);
						$cmd->setEqLogic_id($this->getId());
					} else {
						$command['name'] = $cmd->getName();
						if (isset($command['display'])) {
							unset($command['display']);
						}
					}
					utils::a2o($cmd, $command);
					$cmd->setConfiguration('logicalId', $cmd->getLogicalId());
					$cmd->save();
					if (isset($command['value'])) {
						$link_cmds[$cmd->getId()] = $command['value'];
					}
					if (isset($command['configuration']) && isset($command['configuration']['updateCmdId'])) {
						$link_actions[$cmd->getId()] = $command['configuration']['updateCmdId'];
					}
					$cmd_order++;
				} catch (Exception $exc) {

				}
				$cmd->event('');
			}
		}

		if (count($link_cmds) > 0) {
			foreach ($this->getCmd() as $eqLogic_cmd) {
				foreach ($link_cmds as $cmd_id => $link_cmd) {
					if ($link_cmd == $eqLogic_cmd->getName()) {
						$cmd = cmd::byId($cmd_id);
						if (is_object($cmd)) {
							$cmd->setValue($eqLogic_cmd->getId());
							$cmd->save();
						}
					}
				}
			}
		}
		if (count($link_actions) > 0) {
			foreach ($this->getCmd() as $eqLogic_cmd) {
				foreach ($link_actions as $cmd_id => $link_action) {
					if ($link_action == $eqLogic_cmd->getName()) {
						$cmd = cmd::byId($cmd_id);
						if (is_object($cmd)) {
							$cmd->setConfiguration('updateCmdId', $eqLogic_cmd->getId());
							$cmd->save();
						}
					}
				}
			}
		}
		$this->save();
		if (isset($device['afterInclusionSend']) && $device['afterInclusionSend'] != '') {
			event::add('jeedom::alert', array(
				'level' => 'warning',
				'page' => 'broadlink',
				'message' => __('Envoi des commandes post-inclusion', __FILE__),
			));
			sleep(5);
			$sends = explode('&&', $device['afterInclusionSend']);
			foreach ($sends as $send) {
				foreach ($this->getCmd('action') as $cmd) {
					if (strtolower($cmd->getName()) == strtolower(trim($send))) {
						$cmd->execute();
					}
				}
				sleep(1);
			}

		}
		sleep(2);
		event::add('jeedom::alert', array(
			'level' => 'warning',
			'page' => 'broadlink',
			'message' => '',
		));
	}
}

class broadlinkCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function preSave() {
		if ($this->getType() == 'action') {
			if ($this->getConfiguration('logicalid') == '' && $this->getLogicalId() != '') {
				$this->setConfiguration('logicalid', $this->getLogicalId());
			}
		}
	}

	public function execute($_options = null) {
		if ($this->getType() != 'action') {
			return;
		}
		$data = array();
		$eqLogic = $this->getEqLogic();
		$values = explode(',', $this->getConfiguration('logicalid'));
		foreach ($values as $value) {
			$value = explode(':', $value);
			if (count($value) == 2) {
				switch ($this->getSubType()) {
					case 'slider':
						$finalValue = trim(str_replace('#slider#', $_options['slider'], $value[1]));
						break;
					case 'color':
						$finalValue = trim(str_replace('#color#', $_options['color'], $value[1]));
						break;
					default:
						$finalValue = trim($value[1]);
				}
				$data[trim($value[0])] = $finalValue;
			}
		}
		$data['ip'] = $eqLogic->getConfiguration('ip');
		$data['port'] = $eqLogic->getConfiguration('port');
		$data['type'] = $eqLogic->getConfiguration('device');
		$data['name'] = $eqLogic->getName();
		$data['mac'] = $eqLogic->getLogicalId();
		if (count($data) == 0) {
			return;
		}
		if ($this->getConfiguration('logicalid') == 'refresh') {
			$value = json_encode(array('apikey' => jeedom::getApiKey('broadlink'), 'cmd' => 'send', 'cmdType' => 'refresh', 'mac' => $eqLogic->getLogicalId(), 'device' => $data));
		} else {
			$value = json_encode(array('apikey' => jeedom::getApiKey('broadlink'), 'cmd' => 'send', 'cmdType' => 'command', 'mac' => $eqLogic->getLogicalId(), 'device' => $data));
		}
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
		socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'broadlink'));
		socket_write($socket, $value, strlen($value));
		socket_close($socket);
	}
}
