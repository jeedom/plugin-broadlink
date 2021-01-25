<?php
if (!isConnect('admin')) {
	throw new Exception('Error 401 Unauthorized');
}
$plugin = plugin::byId('broadlink');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
function sortByOption($a, $b) {
	return strcmp($a['name'], $b['name']);
}
if (config::byKey('include_mode', 'broadlink', 0) == 1) {
	echo '<div class="alert jqAlert alert-warning" id="div_inclusionAlert" style="margin : 0px 5px 15px 15px; padding : 7px 35px 7px 15px;">{{Vous etes en mode inclusion. Recliquez sur le bouton d\'inclusion pour sortir de ce mode}}</div>';
} else {
	echo '<div id="div_inclusionAlert"></div>';
}
?>

<div class="row row-overflow">
	<div class="col-lg-12 eqLogicThumbnailDisplay">
		<legend><i class="fa fa-cog"></i>  {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br/>
				<span>{{Ajouter}}</span>
			</div>
			<?php
			if (config::byKey('include_mode', 'broadlink', 0) == 1) {
				echo '<div class="cursor changeIncludeState include card logoSecondary" data-state="0">';
				echo '<i class="fas fa-sign-in-alt fa-rotate-90"></i>';
				echo '<br/>';
				echo '<span>{{Arrêter inclusion}}</span>';
				echo '</div>';
			} else {
				echo '<div class="cursor changeIncludeState include card logoSecondary" data-mode="1" data-state="1">';
				echo '<i class="fas fa-sign-in-alt fa-rotate-90"></i>';
				echo '<br/>';
				echo '<span>{{Mode inclusion}}</span>';
				echo '</div>';
			}
			?>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br/>
				<span>{{Configuration}}</span>
			</div>
			<div class="cursor logoSecondary" id="bt_healthbroadlink">
				<i class="fas fa-medkit"></i>
				<br/>
				<span>{{Santé}}</span>
			</div>
		</div>
		<legend><i class="fa fa-table"></i>  {{Mes équipements broadlink}}</legend>
		<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
		<div class="eqLogicThumbnailContainer">
			<?php
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
				$alternateImg = $eqLogic->getConfiguration('iconModel');
				if (file_exists(dirname(__FILE__) . '/../../core/config/devices/' . $alternateImg . '.png')) {
					echo '<img class="lazy" src="plugins/broadlink/core/config/devices/' . $alternateImg . '.png" />';
				} elseif (file_exists(dirname(__FILE__) . '/../../core/config/devices/' . $eqLogic->getConfiguration('device') . '.png')) {
					echo '<img class="lazy" src="plugins/broadlink/core/config/devices/' . $eqLogic->getConfiguration('device') . '.png" />';
				} else {
					echo '<img src="' . $plugin->getPathImgIcon() . '" />';
				}
				echo "<br/>";
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '</div>';
			}
			?>
		</div>
	</div>
	
	<div class="col-lg-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-success btn-sm eqLogicAction learnCommand roundedLeft"><i class="fa fa-rss"></i> {{Apprendre une commande}}</a><a class="btn btn-warning btn-sm eqLogicAction learnCommandRF"><i class="fa fa-rss"></i> {{Apprendre commande RF Avancée}}</a><a class="btn btn-default btn-sm eqLogicAction" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a><a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
		</ul>
		
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<div class="row">
					<div class="col-sm-6">
						<form class="form-horizontal">
							<fieldset>
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Nom de l'équipement broadlink}}</label>
									<div class="col-sm-4">
										<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="canlearn" style="display : none;" />
										<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="Nom de l'équipement Broadlink"/>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Mac}}</label>
									<div class="col-sm-4">
										<input type="text" class="eqLogicAttr form-control" data-l1key="logicalId" placeholder="Logical ID"/>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label"></label>
									<div class="col-sm-9">
										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Objet parent}}</label>
									<div class="col-sm-4">
										<select class="eqLogicAttr form-control" data-l1key="object_id">
											<option value="">Aucun</option>
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
									<label class="col-sm-3 control-label">{{Catégorie}}</label>
									<div class="col-sm-9">
										<?php
										foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
											echo '<label class="checkbox-inline">';
											echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
											echo '</label>';
										}
										?>
										
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Ip}}</label>
									<div class="col-sm-3">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ip" placeholder="Adresse Ip"/>
									</div>
									<label class="col-sm-1 control-label">{{Port}}</label>
									<div class="col-sm-2">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="port" placeholder="Port"/>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Refresh des infos (en s)}}</label>
									<div class="col-sm-3">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="delay" placeholder="Delai en secondes"/>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label"></label>
									<div class="col-sm-9">
										<label class="checkbox-inline help" data-help="{{Utile si vous avez dupliqué un Broadlink Rm pour séparer les commandes à ne jamais cocher sur l'équipement principal (dans ce cas là la mac doit être obligatoirement terminée par -sub}}"><input type="checkbox" class="eqLogicAttr twoids" data-l1key="configuration" data-l2key="ischild" />{{Sous-Device}}</label>
									</div>
								</div>
								<div class="form-group">
								</fieldset>
							</form>
						</div>
						<div class="col-sm-6">
							<form class="form-horizontal">
								<fieldset>
									<legend><i class="fa fa-info-circle"></i>  {{Informations}}
										<i id="bt_autoDetectModule" class="fa fa-search pull-right cursor" title="{{Recréer les commandes}}"></i>
										<a class="btn btn-primary btn-xs pull-right paramUser" id="bt_configureUser" style="display:none"><i class="fa fa-user"></i>  {{Gestion Utilisateur}}</a>
									</legend>
									<div class="form-group">
										<label class="col-sm-2 control-label">{{Equipement}}</label>
										<div class="col-sm-8">
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
										<label class="col-sm-2 control-label">{{Modèle}}</label>
										<div class="col-sm-8">
											<select class="eqLogicAttr form-control listModel" data-l1key="configuration" data-l2key="iconModel">
											</select>
										</div>
									</div>
									
									<center>
										<img src="core/img/no_image.gif" data-original=".png" id="img_device" class="img-responsive" style="max-height : 250px;"  onerror="this.src='plugins/broadlink/plugin_info/broadlink_icon.png'"/>
									</center>
								</fieldset>
							</form>
						</div>
					</div>
					
				</div>
				<div role="tabpanel" class="tab-pane" id="commandtab">
					
					<a class="btn btn-success btn-sm cmdAction pull-right" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter une commande}}</a>
					<a class="btn btn-primary btn-sm eqLogicAction pull-right" id="btn_sync"><i class="fa fa-spinner"></i> {{Synchroniser}}</a><br/><br/>
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th style="width: 300px;">{{Nom}}</th>
								<th style="width: 130px;">Type</th>
								<th>{{Logical ID (info) ou Commande brute (action)}}</th>
								<th >{{Paramètres}}</th>
								<th style="width: 100px;">{{Options}}</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							
						</tbody>
					</table>
					
				</div>
			</div>
			
		</div>
	</div>
	
	<?php include_file('desktop', 'broadlink', 'js', 'broadlink');?>
	<?php include_file('core', 'plugin.template', 'js');?>
	
	