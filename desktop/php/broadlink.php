<?php
if (!isConnect('admin')) {
	throw new Exception('Error 401 Unauthorized');
}
$plugin = plugin::byId('broadlink');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
// function sortByOption($a, $b) {
// 	return strcmp($a['name'], $b['name']);
// }
if (config::byKey('include_mode', 'broadlink', 0) == 1) {
	echo '<div class="alert jqAlert alert-warning" id="div_inclusionAlert" style="margin : 0px 5px 15px 15px; padding : 7px 35px 7px 15px;">{{Vous êtes en mode inclusion. Recliquez sur le bouton d\'inclusion pour sortir de ce mode}}</div>';
} else {
	echo '<div id="div_inclusionAlert"></div>';
}
?>

<div class="row row-overflow">
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br>
				<span>{{Ajouter}}</span>
			</div>
			<?php
			if (config::byKey('include_mode', 'broadlink', 0) == 1) {
				echo '<div class="cursor changeIncludeState include card logoSecondary" data-state="0">';
				echo '<i class="fas fa-sign-out-alt"></i>';
				echo '<br>';
				echo '<span>{{Arrêter inclusion}}</span>';
				echo '</div>';
			} else {
				echo '<div class="cursor changeIncludeState include card logoSecondary" data-mode="1" data-state="1">';
				echo '<i class="fas fa-sign-in-alt"></i>';
				echo '<br>';
				echo '<span>{{Inclusion}}</span>';
				echo '</div>';
			}
			?>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
			<div class="cursor logoSecondary" id="bt_healthbroadlink">
				<i class="fas fa-medkit"></i>
				<br>
				<span>{{Santé}}</span>
			</div>
		</div>
		<legend><i class="fas fa-podcast"></i> {{Mes Broadlink}}</legend>
		<?php
		if (count($eqLogics) == 0) {
			echo '<br>
		<div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun équipement Template trouvé, cliquer sur "Ajouter" pour commencer}}</div>';
		} else {
			echo '<div class="input-group" style="margin:5px;">';
			echo '<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic">';
			echo '<div class="input-group-btn">';
			echo '<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>';
			echo '<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>';
			echo '</div>';
			echo '</div>';
			echo '<div class="eqLogicThumbnailContainer">';
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
				$alternateImg = $eqLogic->getConfiguration('iconModel');
				if (file_exists(dirname(__FILE__) . '/../../core/config/devices/' . $alternateImg . '.png')) {
					echo '<img src="plugins/broadlink/core/config/devices/' . $alternateImg . '.png">';
				} elseif (file_exists(dirname(__FILE__) . '/../../core/config/devices/' . $eqLogic->getConfiguration('device') . '.png')) {
					echo '<img src="plugins/broadlink/core/config/devices/' . $eqLogic->getConfiguration('device') . '.png">';
				} else {
					echo '<img src="' . $plugin->getPathImgIcon() . '">';
				}
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '<span class="hiddenAsCard displayTableRight hidden">';
				echo ($eqLogic->getConfiguration('ip', '') != '') ? '<span class="label label-info">' . $eqLogic->getConfiguration('ip') . '</span>' : '';
				echo ($eqLogic->getIsVisible() == 1) ? '<i class="fas fa-eye" title="{{Equipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
				echo '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		?>
	</div>

	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-primary btn-sm eqLogicAction learnCommand roundedLeft"><i class="fas fa-rss"></i><span class="hidden-xs"> {{Apprendre commande}}</span>
				</a><a class="btn btn-warning btn-sm eqLogicAction learnCommandRF"><i class="fas fa-broadcast-tower"></i><span class="hidden-xs"> {{Apprendre commande RF Avancée}}</span>
				</a><a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
				</a><a class="btn btn-sm btn-default eqLogicAction" data-action="copy"><i class="fas fa-copy"></i><span class="hidden-xs"> {{Dupliquer}}</span>
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
		</ul>

		<div class="tab-content">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<form class="form-horizontal">
					<fieldset>
						<div class="col-lg-6">
							<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Nom de l'équipement}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="canlearn" style="display:none;">
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Objet parent}}</label>
								<div class="col-sm-6">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										$options = '';
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Catégorie}}</label>
								<div class="col-sm-6">
									<?php
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" >' . $value['name'];
										echo '</label>';
									}
									?>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Options}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked>{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked>{{Visible}}</label>
								</div>
							</div>

							<legend><i class="fas fa-cogs"></i> {{Paramètres spécifiques}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Adresse MAC}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Adresse MAC inversée par paquet de deux sans les deux-points et en minuscule (AA:BB:CC:DD:EE devient eeddccbbaa)}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="logicalId" placeholder="Logical ID">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Adresse IP}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Adresse IP et port}}"></i></sup>
								</label>
								<div class="col-sm-6 input-group">
									<input type="text" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="ip" placeholder="{{Adresse IP}}">
									<span class="input-group-addon">{{Port}}</span>
									<input type="text" class="eqLogicAttr form-control roundedRight" data-l1key="configuration" data-l2key="port" placeholder="{{Port}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Rafraichissement}} <sub>{{secondes}}</sub>
									<sup><i class="fas fa-question-circle tooltips" title="{{Délai de rafraichissement des commandes d'information en secondes}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="delay" placeholder="Délai en secondes">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Sous-équipement}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Ne jamais cocher sur l'équipement principal uniquement en cas de duplication d'un Broadlink RM pour séparer les commandes (l'adresse MAC doit alors obligatoirement finir par}} -sub)"></i></sup>
								</label>
								<div class="col-sm-6">
									<input type="checkbox" class="eqLogicAttr twoids" data-l1key="configuration" data-l2key="ischild">
								</div>
							</div>
						</div>

						<div class="col-lg-6">
							<legend><i class="fas fa-info"></i> {{Informations}}
								<i id="bt_autoDetectModule" class="fas fa-search pull-right cursor" title="{{Recréer les commandes}}"></i>
								<a class="btn btn-primary btn-xs pull-right paramUser" id="bt_configureUser" style="display:none"><i class="fas fa-user"></i> {{Gestion Utilisateur}}</a>
							</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Equipement}}</label>
								<div class="col-sm-6">
									<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="device">
										<?php
										$groups = array();
										foreach (broadlink::devicesParameters() as $key => $info) {
											if (isset($info['groupe'])) {
												$info['key'] = $key;
												if (!isset($groups[$info['groupe']])) {
													$groups[$info['groupe']][0] = $info;
												} else {
													array_push($groups[$info['groupe']], $info);
												}
											}
										}
										ksort($groups);
										foreach ($groups as $group) {
											usort($group, function ($a, $b) {
												return strcmp($a['name'], $b['name']);
											});
											foreach ($group as $key => $info) {
												if ($key == 0) {
													echo '<optgroup label="{{' . $info['groupe'] . '}}">';
												}
												echo '<option value="' . $info['key'] . '">' . $info['name'] . '</option>';
											}
											echo '</optgroup>';
										}
										?>
									</select>
								</div>
							</div>
							<div class="form-group modelList" style="display:none;">
								<label class="col-sm-4 control-label">{{Modèle}}</label>
								<div class="col-sm-6">
									<select class="eqLogicAttr form-control listModel" data-l1key="configuration" data-l2key="iconModel">
									</select>
								</div>
							</div>
							<div class="form-group modelList" style="display:none;">
								<label class="col-sm-4 control-label"></label>
								<div class="col-sm-6">
									<div style="height:220px;display:flex;justify-content:center;align-items:center;">
										<img src="plugins/broadlink/plugin_info/broadlink_icon.png" data-original=".png" id="img_device" class="img-responsive" style="max-height:200px;max-width:200px;" onerror="this.src='plugins/broadlink/plugin_info/broadlink_icon.png'" />
									</div>
								</div>
							</div>
						</div>
					</fieldset>
				</form>
			</div>

			<div role="tabpanel" class="tab-pane" id="commandtab">
				<a class="btn btn-success btn-sm cmdAction pull-right" data-action="add"><i class="fas fa-plus-circle"></i> {{Ajouter une commande}}</a>
				<a class="btn btn-primary btn-sm eqLogicAction pull-right" id="btn_sync"><i class="fas fa-spinner"></i> {{Synchroniser}}</a><br><br>
				<table id="table_cmd" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th class="hidden-xs" style="min-width:50px;width:70px;">ID</th>
							<th style="min-width:200px;width:300px;">{{Nom}}</th>
							<th>{{Type}}</th>
							<th>{{ID Logique (info) ou Commande brute (action)}}</th>
							<th>{{Paramètres}}</th>
							<th>{{Etat}}</th>
							<th style="min-width:260px;">{{Options}}</th>
							<th style="min-width:80px;width:200px;">{{Actions}}</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>

		</div>
	</div>
</div>

<?php include_file('desktop', 'broadlink', 'js', 'broadlink'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
