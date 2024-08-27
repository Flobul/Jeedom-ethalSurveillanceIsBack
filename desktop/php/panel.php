<?php
if (!isConnect()) {
	throw new Exception('{{401 - Accès non autorisé}}');
}

$date = array(
    'start' => init('startDate', date('Y-m-d', strtotime('-1 month ' . date('Y-m-d')))),
    'end' => init('endDate', date('Y-m-d', strtotime('+1 days ' . date('Y-m-d')))),
);

if (init('object_id') == '') {
    $jeeObject = jeeObject::byId($_SESSION['user']->getOptions('defaultDashboardObject'));
} else {
    $jeeObject = jeeObject::byId(init('object_id'));
}
if (!is_object($jeeObject)) {
    $jeeObject = jeeObject::rootObject();
}
if (!is_object($jeeObject)) {
    throw new Exception('{{Aucun objet racine trouvé. Pour en créer un, allez dans Générale -> Objet.<br/> Si vous ne savez pas quoi faire ou que c\'est la premiere fois que vous utilisez Jeedom n\'hésitez pas a consulter cette <a href="http://jeedom.fr/premier_pas.php" target="_blank">page</a>}}');
}

sendVarToJs('eq_id', init('eq_id'));

?>

<div class="row row-overflow">
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <li class="nav-header"><i class="fa fa-bar-chart"></i> {{Surv. Equipement}}</li>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
                $allObject = jeeObject::buildTree();
                foreach ($allObject as $jeeObject) {
                    if ($jeeObject->getIsVisible() == 1 && count($jeeObject->getEqLogic(true, true, 'ethalSurveillanceIsBack')) > 0) {
                        foreach ($jeeObject>getEqLogic() as $eqLogic) {
                            $margin = 5 ;
                            if ($eqLogic->getEqType_name() == 'ethalSurveillanceIsBack' && $eqLogic->getIsEnable()) {
                                if ($eqLogic->getId() == init('eq_id')) {
                                    echo '<li class="cursor li_object active" ><a href="index.php?v=d&m=ethalSurveillanceIsBack&p=panel&eq_id=' . $eqLogic->getId() . '" style="position:relative;left:5 px;">' . $eqLogic->getHumanName(true) . '</a></li>';
                                }else{
                                    echo '<li class="cursor li_object" ><a href="index.php?v=d&m=ethalSurveillanceIsBack&p=panel&eq_id=' . $eqLogic->getId() . '" style="position:relative;left:5 px;">' . $eqLogic->getHumanName(true) . '</a></li>';
                                }
                            }
                        }
                    }
                }
                ?>
           </ul>
       </div>
    </div>
    <div class="col-lg-10 col-md-9 col-sm-8">
        <div class="row">
            <legend style="height: 40px;">
                <span>{{Information de Surveillance}}</span>
                <span>
                    <form class="form-inline pull-right">
                        <div class = "form-group">
                            <label for = "in_startDate" style="font-weight: normal;">{{Du}}</label>
                            <input id="in_startDate" class="form-control input-sm in_datepicker" style="width: 150px;" value="<?php echo $date['start'] ?>"/>
                        </div>
                        <div class = "form-group">
                            <label for = "in_endDate" style="font-weight: normal;">{{Au}}</label>
                            <input id="in_endDate" class="form-control input-sm in_datepicker" style="width: 150px;" value="<?php echo $date['end'] ?>"/>
                        </div>
                        <div class = "form-group">
                            <a class="btn btn-success btn-sm" id='bt_validChangeDate'>{{Ok}}</a>
                        </div>
                        <div class = "form-group">
                            <select class="form-control" id="sel_groupingType" style="width: 200px;">
                                <option value="cumulday">{{Cumul par jour}}</option>
                                <option value="cumulweek">{{Cumul par semaine}}</option>
                                <option value="cumulmonth">{{Cumul par mois}}</option>
                            </select>
                        </div>
                    </form>
                </span>
            </legend>
        </div>
        <div class="row">
            <div class="col-lg-3" id="div_displayEquipement"></div>
            <div class="col-lg-9" id="div_graphic_tpsfct"></div>
        </div>
        <div class="row">
            <legend>{{Equipement surveillé}}</legend>
            <div class="col-lg-12" id="div_displayEquipementMaster"></div>
        </div>
    </div>

</div>

<?php include_file('desktop', 'cumul', 'js', 'ethalSurveillanceIsBack');?>
