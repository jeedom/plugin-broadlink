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
	throw new Exception('401 - {{Accès non autorisé}}');
}
$id = init('id');
$eqLogics = eqLogic::byType('broadlink');
$source = eqLogic::byLogicalId($id, 'broadlink');
?>

<div class="alert alert-info">{{Attention, cette fonctionnalité va copier l'intégralité des commandes de ce Broadlink vers les autres Broadlink. Cela détruit les commandes portant le même nom et existantes sur les cibles.}}</div>
<div>
	<label>{{Veuillez choisir quelle(s) commande(s) vous voulez synchroniser :}}</label> <a class="btn btn-warning btn-xs" id="btn_allCmd"><i class="fa fa-check-square-o"></i> {{Toutes les commandes}}</a></br></br>
	<?php
	foreach ($source->getCmd('action') as $cmd) {
		echo '<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr tosynccmd" logical="' . $cmd->getId() . '"/>' . $cmd->getName() . '</label>';
	}
	?>
</div>
<br />
<div>
	<label>{{Veuillez choisir vers quelle(s) cible(s) vous voulez synchroniser les commandes de ce Broadlink :}}</label> <a class="btn btn-warning btn-xs" id="btn_allDevice"><i class="fa fa-check-square-o"></i> {{Tous les Broadlinks}}</a></br></br>
	<?php
	foreach ($eqLogics as $eqLogic) {
		if ($eqLogic->getLogicalId() != $id && ($eqLogic->getConfiguration('device') == 'rm2' || $eqLogic->getConfiguration('device') == 'rm4')) {
			echo '<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr targets" logical="' . $eqLogic->getId() . '"/>' . $eqLogic->getHumanName(true) . '</label>';
		}
	}
	?>
</div>
<br />
<a class="btn btn-success pull-right" id="bt_syncTo"><i class="fa fa-check-circle"></i> {{Lancer la synchronisation}}</a>
<script>
	$('#bt_syncTo').on('click', function() {
		var source = '<?php echo $id; ?>';
		var targets = [];
		var commands = [];
		$('.targets').each(function() {
			if ($(this).is(':checked')) {
				targets.push($(this).attr('logical'));
			}
		});
		$('.tosynccmd').each(function() {
			if ($(this).is(':checked')) {
				commands.push($(this).attr('logical'));
			}
		});
		$.ajax({ // fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "plugins/broadlink/core/ajax/broadlink.ajax.php", // url du fichier php
			data: {
				action: "synchronise",
				id: source,
				targets: json_encode(targets),
				commands: json_encode(commands),
			},
			dataType: 'json',
			error: function(error) {
				$.fn.showAlert({
					message: error.message,
					level: 'danger'
				})
			},
			success: function(data) { // si l'appel a bien fonctionné
				if (data.state != 'ok') {
					$('#div_alert').showAlert({
						message: data.result,
						level: 'danger'
					});
					return;
				}
				$.fn.showAlert({
					message: 'Commandes copiées',
					level: 'success'
				});
			}
		});
	});

	$('#btn_allCmd').on('click', function() {
		$('.tosynccmd').prop('checked', true);
	});

	$('#btn_allDevice').on('click', function() {
		$('.targets').prop('checked', true);
	});
</script>
