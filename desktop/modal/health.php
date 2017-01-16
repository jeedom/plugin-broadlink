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

if (!isConnect('admin')) {
	throw new Exception('401 Unauthorized');
}
$eqLogics = broadlink::byType('broadlink');
?>

<table class="table table-condensed tablesorter" id="table_healthbroadlink">
	<thead>
		<tr>
			<th>{{Image}}</th>
			<th>{{Module}}</th>
			<th>{{ID}}</th>
			<th>{{Famille}}</th>
			<th>{{Statut}}</th>
			<th>{{Batterie}}</th>
			<th>{{Dernière communication}}</th>
			<th>{{Date création}}</th>
		</tr>
	</thead>
	<tbody>
	 <?php
foreach ($eqLogics as $eqLogic) {
	$profile = strtoupper($eqLogic->getConfiguration('rorg') . '-' . $eqLogic->getConfiguration('func') . '-' . $eqLogic->getConfiguration('type'));
	$alternateImg = $eqLogic->getConfiguration('iconModel');
	if (file_exists(dirname(__FILE__) . '/../../core/config/devices/' . $alternateImg . '.png')) {
		$img = '<img class="lazy" src="plugins/broadlink/core/config/devices/' . $alternateImg . '.png" height="65" width="55" />';
	} elseif (file_exists(dirname(__FILE__) . '/../../core/config/devices/' . $eqLogic->getConfiguration('device') . '.png')) {
		$img = '<img class="lazy" src="plugins/broadlink/core/config/devices/' . $eqLogic->getConfiguration('device') . '.png" height="65" width="55" />';
	} else {
		$img = '<img class="lazy" src="plugins/broadlink/plugin_info/broadlink_icon.png" height="65" width="55" />';
	}
	$signalcmd = $eqLogic->getCmd('info', 'dBM');
	$signal = '';
	if (is_object($signalcmd)) {
		$signal = $signalcmd->execCmd();
	}
	echo '<tr><td>' . $img . '</td><td><a href="' . $eqLogic->getLinkToConfiguration() . '" style="text-decoration: none;">' . $eqLogic->getHumanName(true) . '</a></td>';
	echo '<td><span class="label label-info" style="font-size : 1em;cursor:default;">' . $eqLogic->getId() . '</span></td>';
	echo '<td><span class="label label-info" style="font-size : 1em;cursor:default;">' . $eqLogic->getConfiguration('device') . '</span></td>';
	$status = '<span class="label label-success" style="font-size : 1em;cursor:default;">{{OK}}</span>';
	if ($eqLogic->getStatus('state') == 'nok') {
		$status = '<span class="label label-danger" style="font-size : 1em;cursor:default;">{{NOK}}</span>';
	}
	echo '<td>' . $status . '</td>';
	$battery_status = '<span class="label label-success" style="font-size : 1em;">{{OK}}</span>';
	if ($eqLogic->getCache('batteryStatus') < 20 && $eqLogic->getCache('batteryStatus') != '') {
		$battery_status = '<span class="label label-danger" style="font-size : 1em;">' . $eqLogic->getCache('batteryStatus') . '%</span>';
	} elseif ($eqLogic->getCache('batteryStatus') < 60 && $eqLogic->getCache('batteryStatus') != '') {
		$battery_status = '<span class="label label-warning" style="font-size : 1em;">' . $eqLogic->getCache('batteryStatus') . '%</span>';
	} elseif ($eqLogic->getCache('batteryStatus') > 60 && $eqLogic->getCache('batteryStatus') != '') {
		$battery_status = '<span class="label label-success" style="font-size : 1em;">' . $eqLogic->getCache('batteryStatus') . '%</span>';
	} else {
		$battery_status = '<span class="label label-primary" style="font-size : 1em;" title="{{Secteur}}"><i class="fa fa-plug"></i></span>';
	}
	echo '<td>' . $battery_status . '</td>';
	echo '<td><span class="label label-info" style="font-size : 1em;cursor:default;">' . $eqLogic->getStatus('lastCommunication') . '</span></td>';
	echo '<td><span class="label label-info" style="font-size : 1em;cursor:default;">' . $eqLogic->getConfiguration('createtime') . '</span></td></tr>';
}
?>
	</tbody>
</table>
