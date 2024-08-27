<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('ethalSurveillanceIsBack');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());

$date = array(
    'start' => init('startDate', date('Y-m-d', strtotime('-1 month ' . date('Y-m-d')))),
    'end' => init('endDate', date('Y-m-d', strtotime('+1 days ' . date('Y-m-d')))),
);

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
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
		</div>
		<legend><i class="fas fa-table"></i> {{Mes templates}}</legend>
		<?php
        if (count($eqLogics) == 0) {
            echo '<br><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun équipement Template trouvé, cliquer sur "Ajouter" pour commencer}}</div>';
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
                echo '<img src="' . $eqLogic->getImage() . '"/>';
                echo '<br>';
                echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
                echo '<span class="hiddenAsCard displayTableRight hidden">';
                echo ($eqLogic->getIsVisible() == 1) ? '<i class="fas fa-eye" title="{{Equipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
                echo '</span>';
                echo '</div>';
            }
            echo '</div>';
        }
    ?>
	</div>

	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex;">
			<span class="input-group-btn">
				<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
				</a><a class="btn btn-sm btn-default eqLogicAction" data-action="copy"><i class="fas fa-copy"></i><span class="hidden-xs"> {{Dupliquer}}</span>
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
				</a>
			</span>
		</div>

		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes}}</a></li>
            <li role="presentation"><a href="#actiontab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-cogs"></i> {{Actions}}</a></li>
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
						</div>
						<div class="col-lg-6">
							<legend><i class="fas fa-info"></i> {{Informations}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Description}}</label>
								<div class="col-sm-6">
									<textarea class="form-control eqLogicAttr autogrow" data-l1key="comment"></textarea>
								</div>
							</div>
						</div>

						<div class="col-lg-12">
							 <legend><i class="fas fa-cog"></i> {{Configuration surveillance}}</legend>
				       <div class="form-group">
				           <label class="col-sm-3 control-label">{{Type de commande}}</label>
				           <div class="col-sm-4">
				               <select class="eqLogicAttr form-control" data-l1key='configuration' data-l2key='cmdequipementtype'>
				                   <option value="logique">{{Logique}}</option>
				                   <option value="analogique" >{{Analogique}}</option>
				               </select>
				           </div>
				       </div>
				       <div class="form-group">
				           <label class="col-sm-3 control-label">{{Commande équipement}}</label>
				           <div class="col-sm-4">
				               <div class="input-group">
				                   <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="cmdequipement"/>
				                   <span class="input-group-btn">
				                       <a class="btn btn-default cursor bt_selectCmdExpression" title="{{Rechercher un équipement}}"><i class="fa fa-list-alt"></i></a>
				                   </span>
				               </div>
				           </div>
				           <div class="cmdequipementtype analogique" style="display: none;">
				               <div class="col-sm-4">
				                   <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="general" />{{Compteur Général}}</label>
				               </div>
				           </div>
				           <div class="cmdequipementtype logique" style="display: none;">
				               <div class="col-sm-4">
				                   <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="inverse" />{{Inverser}}</label>
				               </div>
				            </div>
									</div>

			        <div class="cmdequipementtype analogique general" style="display: none;">
			            <div class="form-group">
			                <label class="col-sm-3 control-label">{{Heure de surveillance prévue +/- 2 min (HHMM)}}<span> (1<upper>*</upper>)</label>
			                <div class="col-sm-2">
			                    <div class="input-group">
			                        <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="debutheure" />
			                    </div>
			                </div>
			                <label class="col-sm-3 control-label">{{Valeur surveillance active}}</label>
			                <div class="col-sm-2">
			                    <div class="input-group">
			                        <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="puissance" />
			                    </div>
			                </div>
			            </div>
			        </div>
			        <div class="cmdequipementtype analogique not_general" style="display: none;">
			            <div class="form-group">
			                <label class="col-sm-3 control-label">{{Valeur surveillance inactive}}</label>
			                <div class="col-sm-1">
			                    <div class="input-group">
			                        <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="minpuissance" />
			                    </div>
			                </div>
			                <label class="col-sm-2 control-label">{{Délai valeur surveillance inactive (min)}}</label>
			                <div class="col-sm-1">
			                    <div class="input-group">
			                        <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="minpuissancedelai" />
			                    </div>
			                </div>
			                <label class="col-sm-2 control-label">{{Valeur surveillance active}}</label>
			                <div class="col-sm-1">
			                    <div class="input-group">
			                        <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="maxpuissance" />
			                    </div>
			                </div>
			            </div>
			        </div>
						</div>
						<div class="col-lg-12">
							 <legend><i class="fas fa-list"></i> {{Paramètres surveillance}}</legend>
							 <div class="form-group">
							    <label class="col-sm-3 control-label">{{Jour}}</label>
									<div class="col-sm-6">
			            <ul class="nav nav-tabs" role="tablist">
			                <li role="defaut" class="active"><a href="#defaut" role="tab" aria-controls="defaut" aria-expanded="true" data-toggle="tab" >{{Défaut}}</a></li>
			                <li role="lundi"><a href="#lundi" role="tab" aria-controls="lundi" aria-expanded="false" data-toggle="tab" >{{Lundi}}</a></li>
			                <li role="mardi"><a href="#mardi" aria-controls="mardi" aria-expanded="false" data-toggle="tab" >{{Mardi}}</a></li>
			                <li role="mercredi"><a href="#mercredi" aria-controls="mercredi" aria-expanded="false" data-toggle="tab" >{{Mercredi}}</a></li>
			                <li role="jeudi"><a href="#jeudi" aria-controls="jeudi" aria-expanded="false" data-toggle="tab" >{{Jeudi}}</a></li>
			                <li role="vendredi"><a href="#vendredi" aria-controls="vendredi" aria-expanded="false" data-toggle="tab" >{{Vendredi}}</a></li>
			                <li role="samedi"><a href="#samedi" aria-controls="samedi" aria-expanded="false" data-toggle="tab" >{{Samedi}}</a></li>
			                <li role="dimanche"><a href="#dimanche" aria-controls="dimanche" aria-expanded="false" data-toggle="tab" >{{Dimanche}}</a></li>
			            </ul>
								</div>
							</div>
	            <div class="tab-content">
	                <div role="tabpanel" class="tab-pane active" id="defaut" aria-labelledby="defaut-tab">
	                    <br>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Temps mini surveillance active (min)}}<span> (2<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="tempsmini" />
	                            </div>
	                        </div>
	                    </div>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Temps max surveillance active (min)}}<span> (4<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="tempsmax" />
	                            </div>
	                        </div>
	                    </div>
	                    <div class="cmdequipementtype logique not_general" style="display: none;">
	                        <div class="form-group">
	                            <label class="col-sm-3 control-label">{{Heure prévue surveillance inactive (HHMM)}}<span> (8<upper>*</upper>)</label>
	                            <div class="col-sm-5">
	                                <div class="input-group">
	                                <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="expectedstoppedtime" />
	                                </div>
	                            </div>
	                        </div>
	                        <div class="form-group">
	                            <label class="col-sm-3 control-label">{{Heure prévue surveillance active (HHMM)}}(16<upper>*</upper>)</label>
	                            <div class="col-sm-5">
	                                <div class="input-group">
	                                <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="expectedstartedtime" />
	                                </div>
	                            </div>
	                        </div>
	                    </div>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Valeur compteur haut}}<span> (32<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="cptalarmehaute" />
	                            </div>
	                        </div>
	                    </div>
	                </div>
	                <div role="tabpanel" class="tab-pane" id="lundi" aria-labelledby="lundi-tab">
	                    <br>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Temps mini surveillance active (min)}}<span> (2<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="1tempsmini" />
	                            </div>
	                        </div>
	                    </div>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Temps max surveillance active (min)}}<span> (4<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="1tempsmax" />
	                            </div>
	                        </div>
	                    </div>
	                    <div class="cmdequipementtype logique not_general" style="display: none;">
	                        <div class="form-group">
	                            <label class="col-sm-3 control-label">{{Heure prévue surveillance inactive (HHMM)}}<span> (8<upper>*</upper>)</label>
	                            <div class="col-sm-5">
	                                <div class="input-group">
	                                <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="1expectedstoppedtime" />
	                                </div>
	                            </div>
	                        </div>
	                        <div class="form-group">
	                            <label class="col-sm-3 control-label">{{Heure prévue surveillance active (HHMM)}}(16<upper>*</upper>)</label>
	                            <div class="col-sm-5">
	                                <div class="input-group">
	                                <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="1expectedstartedtime" />
	                                </div>
	                            </div>
	                        </div>
	                    </div>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Valeur compteur haut}}<span> (32<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="1cptalarmehaute" />
	                            </div>
	                        </div>
	                    </div>
	                </div>
	                <div role="tabpanel" class="tab-pane" id="mardi" aria-labelledby="mardi-tab">
	                    <br>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Temps mini surveillance active (min)}}<span> (2<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="2tempsmini" />
	                            </div>
	                        </div>
	                    </div>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Temps max surveillance active (min)}}<span> (4<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="2tempsmax" />
	                            </div>
	                        </div>
	                    </div>
	                    <div class="cmdequipementtype logique not_general" style="display: none;">
	                        <div class="form-group">
	                            <label class="col-sm-3 control-label">{{Heure prévue surveillance inactive (HHMM)}}<span> (8<upper>*</upper>)</label>
	                            <div class="col-sm-5">
	                                <div class="input-group">
	                                <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="2expectedstoppedtime" />
	                                </div>
	                            </div>
	                        </div>
	                        <div class="form-group">
	                            <label class="col-sm-3 control-label">{{Heure prévue surveillance active (HHMM)}}(16<upper>*</upper>)</label>
	                            <div class="col-sm-5">
	                                <div class="input-group">
	                                <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="2expectedstartedtime" />
	                                </div>
	                            </div>
	                        </div>
	                    </div>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Valeur compteur haut}}<span> (32<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="2cptalarmehaute" />
	                            </div>
	                        </div>
	                    </div>
	                </div>
	                <div role="tabpanel" class="tab-pane" id="mercredi" aria-labelledby="mercredi-tab">
	                    <br>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Temps mini surveillance active (min)}}<span> (2<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="3tempsmini" />
	                            </div>
	                        </div>
	                    </div>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Temps max surveillance active (min)}}<span> (4<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="3tempsmax" />
	                            </div>
	                        </div>
	                    </div>
	                    <div class="cmdequipementtype logique not_general" style="display: none;">
	                        <div class="form-group">
	                            <label class="col-sm-3 control-label">{{Heure prévue surveillance inactive (HHMM)}}<span> (8<upper>*</upper>)</label>
	                            <div class="col-sm-5">
	                                <div class="input-group">
	                                <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="3expectedstoppedtime" />
	                                </div>
	                            </div>
	                        </div>
	                        <div class="form-group">
	                            <label class="col-sm-3 control-label">{{Heure prévue surveillance active (HHMM)}}(16<upper>*</upper>)</label>
	                            <div class="col-sm-5">
	                                <div class="input-group">
	                                <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="3expectedstartedtime" />
	                                </div>
	                            </div>
	                        </div>
	                    </div>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Valeur compteur haut}}<span> (32<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="3cptalarmehaute" />
	                            </div>
	                        </div>
	                    </div>
	                </div>
	                <div role="tabpanel" class="tab-pane" id="jeudi" aria-labelledby="jeudi-tab">
	                    <br>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Temps mini surveillance active (min)}}<span> (2<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="4tempsmini" />
	                            </div>
	                        </div>
	                    </div>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Temps max surveillance active (min)}}<span> (4<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="4tempsmax" />
	                            </div>
	                        </div>
	                    </div>
	                    <div class="cmdequipementtype logique not_general" style="display: none;">
	                        <div class="form-group">
	                            <label class="col-sm-3 control-label">{{Heure prévue surveillance inactive (HHMM)}}<span> (8<upper>*</upper>)</label>
	                            <div class="col-sm-5">
	                                <div class="input-group">
	                                <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="4expectedstoppedtime" />
	                                </div>
	                            </div>
	                        </div>
	                        <div class="form-group">
	                            <label class="col-sm-3 control-label">{{Heure prévue surveillance active (HHMM)}}(16<upper>*</upper>)</label>
	                            <div class="col-sm-5">
	                                <div class="input-group">
	                                <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="4expectedstartedtime" />
	                                </div>
	                            </div>
	                        </div>
	                    </div>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Valeur compteur haut}}<span> (32<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="4cptalarmehaute" />
	                            </div>
	                        </div>
	                    </div>
	                </div>
	                <div role="tabpanel" class="tab-pane" id="vendredi" aria-labelledby="vendredi-tab">
	                    <br>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Temps mini surveillance active (min)}}<span> (2<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="5tempsmini" />
	                            </div>
	                        </div>
	                    </div>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Temps max surveillance active (min)}}<span> (4<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="5tempsmax" />
	                            </div>
	                        </div>
	                    </div>
	                    <div class="cmdequipementtype logique not_general" style="display: none;">
	                        <div class="form-group">
	                            <label class="col-sm-3 control-label">{{Heure prévue surveillance inactive (HHMM)}}<span> (8<upper>*</upper>)</label>
	                            <div class="col-sm-5">
	                                <div class="input-group">
	                                <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="5expectedstoppedtime" />
	                                </div>
	                            </div>
	                        </div>
	                        <div class="form-group">
	                            <label class="col-sm-3 control-label">{{Heure prévue surveillance active (HHMM)}}(16<upper>*</upper>)</label>
	                            <div class="col-sm-5">
	                                <div class="input-group">
	                                <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="5expectedstartedtime" />
	                                </div>
	                            </div>
	                        </div>
	                    </div>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Valeur compteur haut}}<span> (32<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="5cptalarmehaute" />
	                            </div>
	                        </div>
	                    </div>
	                </div>
	                <div role="tabpanel" class="tab-pane" id="samedi" aria-labelledby="samedi-tab">
	                    <br>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Temps mini surveillance active (min)}}<span> (2<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="6tempsmini" />
	                            </div>
	                        </div>
	                    </div>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Temps max surveillance active (min)}}<span> (4<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="6tempsmax" />
	                            </div>
	                        </div>
	                    </div>
	                    <div class="cmdequipementtype logique not_general" style="display: none;">
	                        <div class="form-group">
	                            <label class="col-sm-3 control-label">{{Heure prévue surveillance inactive (HHMM)}}<span> (8<upper>*</upper>)</label>
	                            <div class="col-sm-5">
	                                <div class="input-group">
	                                <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="6expectedstoppedtime" />
	                                </div>
	                            </div>
	                        </div>
	                        <div class="form-group">
	                            <label class="col-sm-3 control-label">{{Heure prévue surveillance active (HHMM)}}(16<upper>*</upper>)</label>
	                            <div class="col-sm-5">
	                                <div class="input-group">
	                                <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="6expectedstartedtime" />
	                                </div>
	                            </div>
	                        </div>
	                    </div>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Valeur compteur haut}}<span> (32<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="6cptalarmehaute" />
	                            </div>
	                        </div>
	                    </div>
	                </div>
	                <div role="tabpanel" class="tab-pane" id="dimanche" aria-labelledby="dimanche-tab">
	                    <br>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Temps mini surveillance active (min)}}<span> (2<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="7tempsmini" />
	                            </div>
	                        </div>
	                    </div>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Temps max surveillance active (min)}}<span> (4<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="7tempsmax" />
	                            </div>
	                        </div>
	                    </div>
	                    <div class="cmdequipementtype logique not_general" style="display: none;">
	                        <div class="form-group">
	                            <label class="col-sm-3 control-label">{{Heure prévue surveillance inactive (HHMM)}}<span> (8<upper>*</upper>)</label>
	                            <div class="col-sm-5">
	                                <div class="input-group">
	                                <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="7expectedstoppedtime" />
	                                </div>
	                            </div>
	                        </div>
	                        <div class="form-group">
	                            <label class="col-sm-3 control-label">{{Heure prévue surveillance active (HHMM)}}(16<upper>*</upper>)</label>
	                            <div class="col-sm-5">
	                                <div class="input-group">
	                                <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="7expectedstartedtime" />
	                                </div>
	                            </div>
	                        </div>
	                    </div>
	                    <div class="form-group">
	                        <label class="col-sm-3 control-label">{{Valeur compteur haut}}<span> (32<upper>*</upper>)</label>
	                        <div class="col-sm-5">
	                            <div class="input-group">
	                            <input type="text" class="eqLogicAttr form-control tooltips" data-l1key="configuration" data-l2key="7cptalarmehaute" />
	                            </div>
	                        </div>
	                    </div>
	                </div>
	            </div>
	          </div>
	        </fieldset>
				</form>
        <div>(*) {{Code d'alarme}}</div>
			</div>
      <div role="tabpanel" class="tab-pane" id="commandtab">
				<br><br>
				<div class="table-responsive">
			    <table id="table_cmd" class="table table-bordered table-condensed">
			      <thead>
				        <tr>
								  	<th class="hidden-xs" style="min-width:50px;width:70px;">ID</th>
										<th style="min-width:200px;width:350px;">{{Nom}}</th>
										<th>{{Type}}</th>
										<th style="min-width:260px;">{{Options}}</th>
										<th>{{Etat}}</th>
										<th style="min-width:80px;width:200px;">{{Actions}}</th>
				        </tr>
				    </thead>
			      <tbody>
			      </tbody>
					</table>
				</div>
			</div>

			<div class="tab-pane" id="actiontab">
			    <a class='btn btn-success btn-xs pull-right' id="btn_addethalEqAction"><i class="fa fa-plus-circle"></i> {{Ajouter une action}}</a>
			    <br><br>
			    <form class="form-horizontal">
			        <div id="div_ethalEqAction"></div>
			    </form>
			</div>
    </div>
  </div>
</div>



<?php include_file('desktop', 'ethalSurveillanceIsBack', 'js', 'ethalSurveillanceIsBack');?>
<?php include_file('core', 'plugin.template', 'js');?>
