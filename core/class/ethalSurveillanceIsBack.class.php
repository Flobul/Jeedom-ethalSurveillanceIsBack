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
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class ethalSurveillanceIsBack extends eqLogic
{
    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom */
    public static function cron()
    {
        foreach (eqLogic::byType('ethalSurveillanceIsBack', true) as $eq) {
            $equipementType      = '';
            $etat                = $eq->ethGetValue('etat');
            $pGeneral            = $eq->getConfiguration('general', '');
            $configCmdEquipement = $eq->getConfiguration('cmdequipement', '');

            $cmdEquipement = cmd::byString($configCmdEquipement);
            if (is_object($cmdEquipement)) {
                if ($cmdEquipement->getSubType() === 'numeric') {
                    $equipementType = 'numeric';
                } elseif ($cmdEquipement->getSubType() === 'binary') {
                    $equipementType = 'binary';
                }
            }
            $_option = array('equipement_id' => $eq->getId());
            if ($etat == 1 && $equipementType === 'numeric' && $pGeneral != '1') {
                self::checkequipement($_option);
            }
        }
    }

    /*
     * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom */

    public static function cron5()
    {
        $ethal = eqLogic::byType('ethalSurveillanceIsBack', true);
        foreach ($ethal as $ethalSurveillanceIsBack) {
            $currentTime            = time();
            $expectedStoppedTime    = -1;
            $expectedStartedTime    = -1;
            $expectedStartedTimeMin = -1;
            $expectedStartedTimeMax = -1;
            $expectedStoppedTimeMin = -1;
            $expectedStoppedTimeMax = -1;

            $configDebutheure = $ethalSurveillanceIsBack->getConfiguration('debutheure', '');

            $configExpectedStoppedTime = $ethalSurveillanceIsBack->ethGetDayValue($currentTime, 'expectedstoppedtime', '');
            $configExpectedStartedTime = $ethalSurveillanceIsBack->ethGetDayValue($currentTime, 'expectedstartedtime', '');
            $configTempsMini           = $ethalSurveillanceIsBack->ethGetDayValue($currentTime, 'tempsmini', 0);
            $configTempsMax            = $ethalSurveillanceIsBack->ethGetDayValue($currentTime, 'tempsmax', 0);

            log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : cron5 : min temps set to->' . $configTempsMini .
                    'max temps set to->' . $configTempsMax);

            /* verification debut heure */
            if ($configDebutheure != '') {
                $debutHeure    = \DateTime::createFromFormat('Gi', $configDebutheure)->getTimestamp();
                $debutHeureMin = $debutHeure - 120;
                $debutHeureMax = $debutHeure + 120;
                log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : cron5 : debut heures set to->' . date('H:i:s', $debutHeureMin) . '/' . date('H:i:s', $debutHeure) . '/' . date('H:i:s', $debutHeureMax));
            } else {
                $debutHeure    = $currentTime;
                $debutHeureMin = $debutHeure;
                $debutHeureMax = $debutHeure;
                log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : cron5 : debut heure default set to->' . date('H:i:s', $debutHeureMin) . '/' . date('H:i:s', $debutHeure) . '/' . date('H:i:s', $debutHeureMax));
            }

            if ($configExpectedStoppedTime == '') {
                $expectedStoppedTime    = -1;
                $expectedStoppedTimeMin = -1;
                $expectedStoppedTimeMax = -1;
                log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : cron5 : Arret prévu set to->' . $expectedStoppedTime);
            } else {
                $expectedStoppedTime    = \DateTime::createFromFormat('Gi', $configExpectedStoppedTime)->getTimestamp();
                $expectedStoppedTimeMin = $expectedStoppedTime;
                $expectedStoppedTimeMax = $expectedStoppedTime + 310;
                log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : cron5 : Arret prévu entre-> ' . date('H:i:s', $expectedStoppedTimeMin) . ' et ' . date('H:i:s', $expectedStoppedTimeMax));
            }

            if ($configExpectedStartedTime == '') {
                $expectedStartedTime    = -1;
                $expectedStartedTimeMin = -1;
                $expectedStartedTimeMax = -1;
                log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : cron5 : Marche prévu set to->' . $expectedStartedTime);
            } else {
                $expectedStartedTime    = \DateTime::createFromFormat('Gi', $configExpectedStartedTime)->getTimestamp();
                $expectedStartedTimeMin = $expectedStartedTime;
                $expectedStartedTimeMax = $expectedStartedTime + 310;
                log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : cron5 : Marche prévu entre-> ' . date('H:i:s', $expectedStartedTimeMin) . ' et ' . date('H:i:s', $expectedStartedTimeMax));
            }

            $etat                 = $ethalSurveillanceIsBack->ethGetValue('etat');
            $alarme               = $ethalSurveillanceIsBack->ethGetValue('alarme');
            $currentTempsFct      = $currentTime - $ethalSurveillanceIsBack->getConfiguration('startedtime');
            $currentTempsFctTotal = $ethalSurveillanceIsBack->getConfiguration('previoustpsfct') + $currentTempsFct;


            /* mise à jour des commandes de mesure de temps si l'équipement est actif */
            if ($etat == 1) {
                $fmtCurrentTempsFct      = $ethalSurveillanceIsBack->ethFormatTpsFct($currentTempsFct);
                $fmtCurrentTempsFctTotal = $ethalSurveillanceIsBack->ethFormatTpsFct($currentTempsFctTotal);

                $ethalSurveillanceIsBack->checkAndUpdateCmd('tempsfct', $currentTempsFct);
                $ethalSurveillanceIsBack->checkAndUpdateCmd('tempsfct_hms', $fmtCurrentTempsFct);
                $ethalSurveillanceIsBack->checkAndUpdateCmd('tempsfcttotal', $currentTempsFctTotal);
                $ethalSurveillanceIsBack->checkAndUpdateCmd('tempsfcttotal_hms', $fmtCurrentTempsFctTotal);
            }

            /* Alarme code 1 si pas demarré a l'heure prevu + temps mini de fonctionnement et debut heure non vide */
            if ($currentTime >= ($debutHeure + ($configTempsMini * 60)) && $etat == 0 && $configTempsMini != 0) {
                $ethalSurveillanceIsBack->ethAlarmeCode(1);
                if ($alarme === 0) {
                    $alarme = 1;
                    $ethalSurveillanceIsBack->checkAndUpdateCmd('alarme', 1);
                    self::doAction('ethalEqAction', 'alarme', 0, $ethalSurveillanceIsBack);
                    log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : cron5 : Alarme debut heure->' . date('H:i:s', $debutHeure));
                }
            }
            /* alarme code 4 */
            if ($currentTempsFct >= ($configTempsMax * 60) && $etat === 1 && $configTempsMax != 0) {
                $ethalSurveillanceIsBack->ethAlarmeCode(4);
                if ($alarme == 0) {
                    $alarme = 1;
                    $ethalSurveillanceIsBack->checkAndUpdateCmd('alarme', 1);
                    self::doAction('ethalEqAction', 'alarme', 0, $ethalSurveillanceIsBack);
                    log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : cron5 : Alarme Temps Max->' . $currentTempsFct);
                }
            }

            /* alarme code 8 */
            if ($currentTime >= $expectedStoppedTimeMin && $currentTime <= $expectedStoppedTimeMax && $etat == 1 && $expectedStoppedTime != -1) {
                $ethalSurveillanceIsBack->ethAlarmeCode(8);
                if ($alarme == 0) {
                    $alarme = 1;
                    $ethalSurveillanceIsBack->checkAndUpdateCmd('alarme', 1);
                    self::doAction('ethalEqAction', 'alarme', 0, $ethalSurveillanceIsBack);
                    log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : cron5 : Alarme Expected Stopped Time->' . date('H:i:s', $expectedStoppedTimeMin) . '<' . date('H:i:s', $currentTime) . '>' . date('H:i:s', $expectedStoppedTimeMax));
                }
            }

            /* alarme code 16 */
            if ($currentTime >= $expectedStartedTimeMin && $currentTime <= $expectedStartedTimeMax && $etat == 0 && $expectedStartedTime != -1) {
                $ethalSurveillanceIsBack->ethAlarmeCode(16);
                if ($alarme == 0) {
                    $alarme = 1;
                    $ethalSurveillanceIsBack->checkAndUpdateCmd('alarme', 1);
                    self::doAction('ethalEqAction', 'alarme', 0, $ethalSurveillanceIsBack);
                    log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : cron5 : Alarme Expected Started Time->' . date('H:i:s', $expectedStartedTimeMin) . '<' . date('H:i:s', $currentTime) . '>' . date('H:i:s', $expectedStartedTimeMax));
                }
            }
        }
    }

    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {

      }
     */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDayly() {

      }
     */

    public static function deadCmd()
    {
        /* @var $matches array */
        $result = [];
        foreach (eqLogic::byType('ethalSurveillanceIsBack') as $ethalSurveillanceIsBack) {
            /* @var $matches array */
            $matches = [];
            preg_match_all("/#([0-9]*)#/", $ethalSurveillanceIsBack->getConfiguration('cmdequipement', ''), $matches);
            foreach ($matches[1] as $cmd_id) {
                if (!cmd::byId(str_replace('#', '', $cmd_id))) {
                    $result[] = array('detail' => 'Ethal Surveillance ' . $ethalSurveillanceIsBack->getHumanName(), 'help' => 'Type de commande', 'who' => '#' . $cmd_id . '#');
                }
            }
        }
        return $result;
    }

    /* public function preInsert() {

      }

      public function postInsert() {

      }

      public function preSave() {

      }

      public function postSave() {

      }

      public function preUpdate() {

      } */

    public function postUpdate()
    {
        $this->ethCreateCmd('ethalSurveillanceIsBack');
    }

    public function preRemove()
    {
        $listener = listener::byClassAndFunction('ethalSurveillanceIsBack', 'checkequipement', ['equipement_id' => $this->getId()]);
        if (is_object($listener)) {
            log::add('ethalSurveillanceIsBack', 'debug', 'Suppression du listener->checkequipement');
            $listener->remove();
        }
    }

    /*  public function postRemove() {

      }

      /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
      public function toHtml($_version = 'dashboard') {

      }
     */

    /* public Ethal Surveillance plugin function */
    public function checkequipement($_option)
    {
        log::add('ethalSurveillanceIsBack', 'debug', 'checkequipement started');

        $ethalSurveillanceIsBack = ethalSurveillanceIsBack::byId($_option['equipement_id']);
        if (is_object($ethalSurveillanceIsBack) && $ethalSurveillanceIsBack->getIsEnable() == 1) {

            log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : equipement trouvé et actif');

            $equipementType         = '';
            $currentTime            = time();
            $minPuissanceDelaiReach = 0;

            $expectedStoppedTime    = -1;
            $expectedStartedTime    = -1;
            $expectedStartedTimeMin = -1;
            $expectedStartedTimeMax = -1;
            $expectedStoppedTimeMin = -1;
            $expectedStoppedTimeMax = -1;

            $etat   = $ethalSurveillanceIsBack->ethGetValue('etat');
            $alarme = $ethalSurveillanceIsBack->ethGetValue('alarme');

            $configCmdEquipement = $ethalSurveillanceIsBack->getConfiguration('cmdequipement', '');
            $puissance           = $ethalSurveillanceIsBack->getConfiguration('puissance', -100000);
            $minPuissance        = $ethalSurveillanceIsBack->getConfiguration('minpuissance', 0);
            $maxPuissance        = $ethalSurveillanceIsBack->getConfiguration('maxpuissance', -10000);
            $pGeneral            = $ethalSurveillanceIsBack->getConfiguration('general', '');
            $memoPuissance       = $ethalSurveillanceIsBack->getConfiguration('memopuissance', ''); // Feature used
            $configDebutheure    = $ethalSurveillanceIsBack->getConfiguration('debutheure', '');

            $minPuissanceDelai     = $ethalSurveillanceIsBack->getConfiguration('minpuissancedelai', 0);
            $memoCurrentTime       = $ethalSurveillanceIsBack->getConfiguration('memocurrenttime', 0);
            $memoCurrentTimeStatus = $ethalSurveillanceIsBack->getConfiguration('memocurrenttimestatus', 0);

            $configExpectedStoppedTime = $ethalSurveillanceIsBack->ethGetDayValue($currentTime, 'expectedstoppedtime', '');
            $configExpectedStartedTime = $ethalSurveillanceIsBack->ethGetDayValue($currentTime, 'expectedstartedtime', '');
            $configTempsMini           = $ethalSurveillanceIsBack->ethGetDayValue($currentTime, 'tempsmini', 0);
            $configTempsMax            = $ethalSurveillanceIsBack->ethGetDayValue($currentTime, 'tempsmax', 0);
            $configCptAlarmeHaute      = $ethalSurveillanceIsBack->ethGetDayValue($currentTime, 'cptalarmehaute', 0);

            log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : min temps set to ->' . $configTempsMini .
                    ' max temps set to ->' . $configTempsMax .
                    ' min puissance set to->' . $minPuissance .
                    ' max puissance set to->' . $maxPuissance .
                    ' puissance set to ->' . $puissance);

            /* verification debut heure */
            if ($configDebutheure != '') {
                $debutHeure    = \DateTime::createFromFormat('Gi', $configDebutheure)->getTimestamp();
                $debutHeureMin = $debutHeure - 120;
                $debutHeureMax = $debutHeure + 120;
                log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : debut heures set to->' . date('H:i:s', $debutHeureMin) . '/' . date('H:i:s', $debutHeure) . '/' . date('H:i:s', $debutHeureMax));
            } else {
                $debutHeure    = $currentTime;
                $debutHeureMin = $debutHeure;
                $debutHeureMax = $debutHeure;
                log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : debut heure default set to->' . date('H:i:s', $debutHeureMin) . '/' . date('H:i:s', $debutHeure) . '/' . date('H:i:s', $debutHeureMax));
            }

            if ($configExpectedStoppedTime == '') {
                $expectedStoppedTime = -1;
                log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : Arret prévu set to->' . $expectedStoppedTime);
            } else {
                $expectedStoppedTime    = \DateTime::createFromFormat('Gi', $configExpectedStoppedTime)->getTimestamp();
                $expectedStoppedTimeMin = $expectedStoppedTime;
                $expectedStoppedTimeMax = $expectedStoppedTime + 310;
                log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : Arret prévu entre ->' . date('H:i:s', $expectedStoppedTimeMin) . ' et ' . date('H:i:s', $expectedStoppedTimeMax));
            }

            if ($configExpectedStartedTime == '') {
                $expectedStartedTime = -1;
                log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : Marche prévu set to->' . $expectedStartedTime);
            } else {
                $expectedStartedTime    = \DateTime::createFromFormat('Gi', $configExpectedStartedTime)->getTimestamp();
                $expectedStartedTimeMin = $expectedStartedTime;
                $expectedStartedTimeMax = $expectedStartedTime + 310;
                log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : Marche prévu entre ->' . date('H:i:s', $expectedStartedTimeMin) . ' et ' . date('H:i:s', $expectedStartedTimeMax));
            }

            /* Verification de la commande de mesure de l'equipement */
            $cmdEquipement = cmd::byString($configCmdEquipement);
            if (is_object($cmdEquipement)) {
                if ($cmdEquipement->getSubType() === 'numeric') {
                    $equipementType = 'numeric';
                    log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : Numeric : Power measurement name->' . $cmdEquipement->getHumanName() . ' Power measurement value->' . $cmdEquipement->execCmd());
                } elseif ($cmdEquipement->getSubType() === 'binary') {
                    $equipementType = 'binary';
                    log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : Binary : Equipement Cmd name->' . $cmdEquipement->getHumanName() . ' Cmd equipement state->' . $cmdEquipement->execCmd());
                } else {
                    log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : NOT Binary/Numeric : Equipement Cmd name->' . $cmdEquipement->getHumanName());
                }
            } else {
                log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : Equipment cmd not found');
            }

            $cmdValue = $cmdEquipement->execCmd();
            $compteur = $ethalSurveillanceIsBack->getCmd(null, 'count')->execCmd();

            if ($pGeneral == '1' && $equipementType == 'numeric') {
                $cmdValue          = $cmdValue - $puissance;
                $minPuissance      = 0;
                $maxPuissance      = 0;
                $minPuissanceDelai = 0;
                log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : cpt général =1, min/max cmd set to->0');
            }

            if ($equipementType == 'binary') {
                $minPuissance      = 0;
                $maxPuissance      = 1;
                $minPuissanceDelai = 0;
                $inverse           = $ethalSurveillanceIsBack->getConfiguration('inverse', '0');
                if ($inverse == '0') {
                    $cmdValue = $cmdValue;
                }
                if ($inverse == '1') {
                    $cmdValue = !$cmdValue;
                }
                log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : binary cmd, max cmd set to->1 min cmd set to->0 inverse->' . $inverse);
            }
            log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : value etat->' . $etat . ' value cmd ->' . $cmdValue . ' min cmd->' . $minPuissance . ' max cmd->' . $maxPuissance);
            /*
              0903 < 2330 < 0907
              2327 < 2330 < 2333
             */

            if ($equipementType == 'numeric' || $equipementType == 'binary') {

                if ($cmdValue >= $maxPuissance && $etat == 0 && ( ($currentTime >= $debutHeureMin && $currentTime <= $debutHeureMax) || $debutHeureMin == $debutHeure)) {

                    $etat = 1;
                    $ethalSurveillanceIsBack->checkAndUpdateCmd('etat', $etat);

                    $ethalSurveillanceIsBack->checkAndUpdateCmd('startedtime', date('H:i:s', $currentTime));
                    $ethalSurveillanceIsBack->checkAndUpdateCmd('stoppedtime', '-');

                    $ethalSurveillanceIsBack->checkAndUpdateCmd('count', $compteur + 1);

                    $ethalSurveillanceIsBack->setConfiguration('startedtime', $currentTime);
                    $ethalSurveillanceIsBack->setConfiguration('memopuissance', $cmdValue);
                    $ethalSurveillanceIsBack->save();

                    //$alCode32 n'est pas utilsé, à supprimer ?
                    $alCode32 = $ethalSurveillanceIsBack->getCmd(null, 'code_alarme')->getConfiguration('ethalarmecode32');
                    $alarme   = $ethalSurveillanceIsBack->ethGetValue('alarme');
                    if ($alarme == 1) {
                        self::ethResetAlarme($ethalSurveillanceIsBack);
                    }
                    $alarme = 0;

                    if (($compteur + 1) >= $configCptAlarmeHaute && $configCptAlarmeHaute != 0) {
                        $ethalSurveillanceIsBack->ethAlarmeCode(32);
                        if ($alarme == 0) {
                            $alarme = 1;
                            $ethalSurveillanceIsBack->checkAndUpdateCmd('alarme', 1);
                            self::doAction('ethalEqAction', 'alarme', 0, $ethalSurveillanceIsBack);
                            log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : Valeur compteur haute->' . $ethalSurveillanceIsBack->getCmd(null, 'count')->execCmd());
                        }
                        log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : value change etat->' . $etat . ' compteur->' . $ethalSurveillanceIsBack->getCmd(null, 'count')->execCmd());
                    }
                    self::doAction('ethalEqAction', 'etat', 0, $ethalSurveillanceIsBack);
                }

                log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : value memoCurrentTimeStatus->' . $memoCurrentTimeStatus . ' value minPuissanceDelaiReach->' . $minPuissanceDelaiReach . ' value memoCurrentTime+minPuissanceDelai->' . ($memoCurrentTime + ($minPuissanceDelai * 60)));
                log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : value change etat->' . $etat . ' compteur->' . $ethalSurveillanceIsBack->getCmd(null, 'count')->execCmd());

                /* gestion du delai sur la puissance mini */
                if ($cmdValue <= $minPuissance && $etat == 1 && $equipementType == 'numeric' && $pGeneral != '1' && $memoCurrentTimeStatus == 0) {
                    $memoCurrentTime       = $currentTime;
                    $memoCurrentTimeStatus = 1;
                    $ethalSurveillanceIsBack->setConfiguration('memocurrenttime', $memoCurrentTime);
                    $ethalSurveillanceIsBack->setConfiguration('memocurrenttimestatus', $memoCurrentTimeStatus);
                    $ethalSurveillanceIsBack->save();
                }

                if ($cmdValue >= $minPuissance && $etat == 1 && $equipementType == 'numeric' && $pGeneral != '1' && $memoCurrentTimeStatus == 1) {
                    $memoCurrentTime       = $currentTime;
                    $memoCurrentTimeStatus = 0;
                    $ethalSurveillanceIsBack->setConfiguration('memocurrenttime', $memoCurrentTime);
                    $ethalSurveillanceIsBack->setConfiguration('memocurrenttimestatus', $memoCurrentTimeStatus);
                    $ethalSurveillanceIsBack->save();
                }
                /* End gestion du delai sur la puissance mini */

                if ($currentTime >= ($memoCurrentTime + ($minPuissanceDelai * 60)) && $memoCurrentTimeStatus == 1) {
                    $minPuissanceDelaiReach = 1;
                }

                log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : value memoCurrentTimeStatus->' . $memoCurrentTimeStatus . ' value minPuissanceDelaiReach->' . $minPuissanceDelaiReach . ' value memoCurrentTime+minPuissanceDelai->' . ($memoCurrentTime + ($minPuissanceDelai * 60)));
                log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : value change etat->' . $etat . ' compteur->' . $ethalSurveillanceIsBack->getCmd(null, 'count')->execCmd());


                if (($cmdValue <= $minPuissance && ($equipementType == 'binary' || $pGeneral == '1') && $etat == 1) || ($minPuissanceDelaiReach == 1 && $etat == 1)) {

                    $etat                   = 0;
                    $minPuissanceDelaiReach = 0;

                    $ethalSurveillanceIsBack->setConfiguration('memocurrenttime', 0);
                    $ethalSurveillanceIsBack->setConfiguration('memocurrenttimestatus', 0);

                    $ethalSurveillanceIsBack->checkAndUpdateCmd('etat', $etat);
                    $ethalSurveillanceIsBack->checkAndUpdateCmd('stoppedtime', date('H:i:s', $currentTime));

                    $ethalSurveillanceIsBack->setConfiguration('stoppedtime', $currentTime);
                    $ethalSurveillanceIsBack->setConfiguration('memopuissance', 0);

                    $currentTempsFct      = $currentTime - $ethalSurveillanceIsBack->getConfiguration('startedtime');
                    $currentTempsFctTotal = $ethalSurveillanceIsBack->getConfiguration('previoustpsfct') + $currentTempsFct;

                    $ethalSurveillanceIsBack->setConfiguration('previoustpsfct', $currentTempsFctTotal);
                    $ethalSurveillanceIsBack->save();

                    $fmtCurrentTempsFct      = $ethalSurveillanceIsBack->ethFormatTpsFct($currentTempsFct);
                    $fmtCurrentTempsFctTotal = $ethalSurveillanceIsBack->ethFormatTpsFct($currentTempsFctTotal);

                    $ethalSurveillanceIsBack->checkAndUpdateCmd('tempsfct', $currentTempsFct);
                    $ethalSurveillanceIsBack->checkAndUpdateCmd('tempsfct_hms', $fmtCurrentTempsFct);
                    $ethalSurveillanceIsBack->checkAndUpdateCmd('tempsfcttotal', $currentTempsFctTotal);
                    $ethalSurveillanceIsBack->checkAndUpdateCmd('tempsfcttotal_hms', $fmtCurrentTempsFctTotal);

                    /* Alarme Code 2 */
                    if ($currentTempsFct <= ($configTempsMini * 60) && $configTempsMini != 0) {
                        $ethalSurveillanceIsBack->ethAlarmeCode(2);
                        if ($alarme == 0) {
                            $alarme = 1;
                            $ethalSurveillanceIsBack->checkAndUpdateCmd('alarme', 1);
                            self::doAction('ethalEqAction', 'alarme', 0, $ethalSurveillanceIsBack);
                            log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : Temps min, alarme set to->1');
                        }
                    }

                    if ($currentTempsFct >= ($configTempsMax * 60) && $configTempsMax != 0) {
                        $ethalSurveillanceIsBack->ethAlarmeCode(4);
                        if ($alarme == 0) {
                            $alarme = 1;
                            $ethalSurveillanceIsBack->checkAndUpdateCmd('alarme', 1);
                            self::doAction('ethalEqAction', 'alarme', 0, $ethalSurveillanceIsBack);
                            log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : Temps max, alarme set to->1');
                        }
                    }

                    self::doAction('ethalEqAction', 'etat', 1, $ethalSurveillanceIsBack);

                    log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : Started Time->' . $ethalSurveillanceIsBack->getConfiguration('startedtime') . ' Stopped Time->' . $currentTime);
                    log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : value Temps mini(sec)->' . ($configTempsMini * 60) . ' Valeur Current Temps de fct->' . $currentTempsFct);
                    log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : value Temps max(sec)->' . ($configTempsMax * 60) . ' Valeur Current Temps de fct->' . $currentTempsFct);
                    log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : value change etat->' . $etat);
                }
            }

            $currentTempsFct      = $currentTime - $ethalSurveillanceIsBack->getConfiguration('startedtime');
            $currentTempsFctTotal = $ethalSurveillanceIsBack->getConfiguration('previoustpsfct') + $currentTempsFct;

            /* Alarme si pas demarré a l'heure prevu + temps mini de fonctionnement et debut heure non vide */
            if ($currentTime >= ($debutHeure + ($configTempsMini * 60)) && $etat == 0 && $configTempsMini != 0) {
                $ethalSurveillanceIsBack->ethAlarmeCode(1);
                if ($alarme == 0) {
                    $alarme = 1;
                    $ethalSurveillanceIsBack->checkAndUpdateCmd('alarme', 1);
                    self::doAction('ethalEqAction', 'alarme', 0, $ethalSurveillanceIsBack);
                    log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : Alarme debut heure->' . $debutHeure);
                }
            }
            if ($currentTempsFct >= ($configTempsMax * 60) && $etat == 1 && $configTempsMax != 0) {
                $ethalSurveillanceIsBack->ethAlarmeCode(4);
                if ($alarme == 0) {
                    $alarme = 1;
                    $ethalSurveillanceIsBack->checkAndUpdateCmd('alarme', 1);
                    self::doAction('ethalEqAction', 'alarme', 0, $ethalSurveillanceIsBack);
                    log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : Alarme Temps max->' . $currentTempsFct);
                }
            }

            if ($currentTime >= $expectedStoppedTimeMin && $currentTime <= $expectedStoppedTimeMax && $etat == 1 && $expectedStoppedTime != -1) {
                $ethalSurveillanceIsBack->ethAlarmeCode(8);
                if ($alarme == 0) {
                    $alarme = 1;
                    $ethalSurveillanceIsBack->checkAndUpdateCmd('alarme', 1);
                    self::doAction('ethalEqAction', 'alarme', 0, $ethalSurveillanceIsBack);
                    log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : Alarme Expected Stopped Time->' . date('H:i:s', $expectedStoppedTimeMin) . '<' . date('H:i:s', $currentTime) . '>' . date('H:i:s', $expectedStoppedTimeMax));
                }
            }

            if ($currentTime >= $expectedStartedTimeMin && $currentTime <= $expectedStartedTimeMax && $etat == 0 && $expectedStartedTime != -1) {
                $ethalSurveillanceIsBack->ethAlarmeCode(16);
                if ($alarme == 0) {
                    $alarme = 1;
                    $ethalSurveillanceIsBack->checkAndUpdateCmd('alarme', 1);
                    self::doAction('ethalEqAction', 'alarme', 0, $ethalSurveillanceIsBack);
                    log::add('ethalSurveillanceIsBack', 'debug', $ethalSurveillanceIsBack->getName() . ' : checkequipement : Alarme Expected Started Time->' . date('H:i:s', $expectedStartedTimeMin) . '<' . date('H:i:s', $currentTime) . '>' . date('H:i:s', $expectedStartedTimeMax));
                }
            }

            if ($etat == 1) {
                $fmtCurrentTempsFct      = $ethalSurveillanceIsBack->ethFormatTpsFct($currentTempsFct);
                $fmtCurrentTempsFctTotal = $ethalSurveillanceIsBack->ethFormatTpsFct($currentTempsFctTotal);

                $ethalSurveillanceIsBack->checkAndUpdateCmd('tempsfct', $currentTempsFct);
                $ethalSurveillanceIsBack->checkAndUpdateCmd('tempsfct_hms', $fmtCurrentTempsFct);
                $ethalSurveillanceIsBack->checkAndUpdateCmd('tempsfcttotal', $currentTempsFctTotal);
                $ethalSurveillanceIsBack->checkAndUpdateCmd('tempsfcttotal_hms', $fmtCurrentTempsFctTotal);
            }
        }
    }

    /* plugin private static function */

    private static function ethResetAlarme($eq)
    {

        $eq->checkAndUpdateCmd('alarme', 0);
        $eq->checkAndUpdateCmd('code_alarme', 0);
        $eq->getCmd(null, 'code_alarme')->setConfiguration('ethalarmecode1', 0);
        $eq->getCmd(null, 'code_alarme')->setConfiguration('ethalarmecode2', 0);
        $eq->getCmd(null, 'code_alarme')->setConfiguration('ethalarmecode4', 0);
        $eq->getCmd(null, 'code_alarme')->setConfiguration('ethalarmecode8', 0);
        $eq->getCmd(null, 'code_alarme')->setConfiguration('ethalarmecode16', 0);
        $eq->getCmd(null, 'code_alarme')->setConfiguration('ethalarmecode32', 0);

        $eq->getCmd(null, 'code_alarme')->save();
        self::doAction('ethalEqAction', 'alarme', 1, $eq);
        log::add('ethalSurveillanceIsBack', 'debug', $eq->getName() . ' : ethResetAlarme : Alarme Reset');
    }

    private function ethAlarmeCode($code)
    {

        //$alCode = $eq->getConfiguration('alarmecode'.$code);
        $alCode = $this->getCmd(null, 'code_alarme')->getConfiguration('ethalarmecode' . $code);
        log::add('ethalSurveillanceIsBack', 'debug', $this->getName() . ' : ethAlarmeCode : check Alarme Code ' . $code . ' current value->' . $alCode);

        if ($alCode != 1) {
            $this->checkAndUpdateCmd('code_alarme', $this->getCmd(null, 'code_alarme')->execCmd() + $code);
            $this->getCmd(null, 'code_alarme')->setConfiguration('ethalarmecode' . $code, 1);
            $this->getCmd(null, 'code_alarme')->save();
            log::add('ethalSurveillanceIsBack', 'debug', $this->getName() . ' : ethAlarmeCode : Alarme Code set to->' . $code);
        }
    }

    private function ethFormatTpsFct($_val)
    {
        $result = '';
        if ((floor($_val / (3600 * 24))) == 0) {
            $result = gmdate('H:i:s', $_val);
        } else {
            $result = strval(floor($_val / (3600 * 24))) . 'j ' . gmdate('H:i:s', $_val);
        }
        log::add('ethalSurveillanceIsBack', 'debug', 'Function : ethFormatTpsFct : Temps Fct->' . $result);
        return $result;
    }

    private function ethGetValue($_name,$_default = 0)
    {
        $result = $this->getCmd(null, $_name)->execCmd();
        if ($result === null || !is_int($result)) {
            $this->checkAndUpdateCmd($_name, $_default);
            $result = $_default;
            log::add('ethalSurveillanceIsBack', 'debug', $this->getName() . ' : ethGetValue : ' . $_name . ' current Type value->' . gettype($result) . ' return init value->' . $result);
        } else {
            log::add('ethalSurveillanceIsBack', 'debug', $this->getName() . ' : ethGetValue : ' . $_name . ' return value->' . $result);
        }
        return $result;
    }

    private function ethGetDayValue($_currentTime, $_key, $_default)
    {
        $dayConfig = $this->getConfiguration(date('N', $_currentTime) . $_key, $_default);
        $result    = $this->getConfiguration($_key, $_default);
        if ($dayConfig != $_default) {
            $result = $dayConfig;
        }
        return $result;
    }

    private static function doAction($_action, $_type, $_sens, $_eq)
    {

        foreach ($_eq->getConfiguration($_action) as $action) {
            $cmd = cmd::byId(str_replace('#', '', $action['cmd']));
            /* A revoir pas tres clair
              if (is_object($cmd) && $this->getId() == $cmd->getEqLogic_id()) {
              log::add('ethalSurveillanceIsBack', 'debug', 'Action-> Oups Cmd probleme');
              continue;
              }
             */
            // IF a revoir pas terrible
            if ($action['actionSens'] == $_sens && $action['actionType'] == $_type) {
                try {
                    $options = [];
                    if (isset($action['options'])) {
                        $options = $action['options'];
                    }
                    log::add('ethalSurveillanceIsBack', 'debug', 'Prepare for Action->' . $action['cmd'] . ' type->' . $action['actionType'] . '/' . $_type . ' Sens->' . $action['actionSens'] . '/' . $_sens);
                    if ($options['enable'] == '1') {
                        log::add('ethalSurveillanceIsBack', 'debug', 'Done Action->' . $action['cmd'] . ' type->' . $action['actionType'] . '/' . $_type . ' Sens->' . $action['actionSens'] . '/' . $_sens);
                        scenarioExpression::createAndExec('action', $action['cmd'], $options);
                    }
                } catch (Exception $e) {
                    log::add('ethalSurveillanceIsBack', 'error', __('Erreur lors de l\'éxecution de ', __FILE__) . $action['cmd'] . __('. Détails : ', __FILE__) . $e->getMessage());
                }
            }
        }
    }

    private function ethCreateCmd($_name)
    {
        /* commande alarme code fonctionnement
          debut heure : 1
          Temps mini : 2
          Temps maxi : 4
          Arret prevu : 8
          Marche prevu : 16
          Compteur haut : 32
         */
        /* verify if the file existe */
        if (!is_file(dirname(__FILE__) . '/../config/devices/' . $_name . '.json')) {
            log::add('ethalSurveillanceIsBack', 'error', 'fichier commande pas trouvé');
            return;
        }
        /* verify if the content is json type */
        $content = file_get_contents(dirname(__FILE__) . '/../config/devices/' . $_name . '.json');
        if (!is_json($content)) {
            log::add('ethalSurveillanceIsBack', 'error', 'fichier commande impossible à lire');
            return;
        }
        /* verify if the content is well formated */
        $device = json_decode($content, true);
        if (!is_array($device) || !isset($device['commands'])) {
            log::add('ethalSurveillanceIsBack', 'error', 'format fichier commande json mauvais');
            return;
        }
        log::add('ethalSurveillanceIsBack', 'debug', 'fichier commande ok');
        /* create command */
        $commands = $device['commands'];
        foreach ($commands as $command) {
            $cmd            = null;
            $existingCmds   = $this->getCmd();
            /* locate existing command */
            foreach ($existingCmds as $existingCmd) {
                if ((isset($command['logicalId']) && $existingCmd->getLogicalId() == $command['logicalId'])) {
                    $cmd = $existingCmd;
                    break;
                }
            }
            // if not exist create the command
            if ($cmd === null || !is_object($cmd)) {
                $cmd = new ethalSurveillanceIsBackCmd();
                $cmd->setEqLogic_id($this->getId());
                utils::a2o($cmd, $command);
                $cmd->save();
                log::add('ethalSurveillanceIsBack', 'debug', 'Creation de la commande->' . $command['logicalId']);
            /*
            } else {
                $cmd = ethalSurveillanceIsBackCmd::byEqLogicIdAndLogicalId($this->getId(), $command['logicalId']);
                utils::a2o($cmd, $command);
                $cmd->save();
                log::add('ethalSurveillanceIsBack', 'debug', 'Mise à jour de la commande->' . $command['logicalId']);
            */
            }

        }

        /* listener de la mesure de puissance our de la commande d'etat */
        if ($this->getIsEnable() == 1 && $this->getConfiguration('cmdequipement') !== null) {
            $listener = listener::byClassAndFunction('ethalSurveillanceIsBack', 'checkequipement', array('equipement_id' => $this->getId()));
            if (!is_object($listener)) {
                log::add('ethalSurveillanceIsBack', 'debug', 'Création du listener->checkequipement');
                $listener = new listener();
            }
            $listener->setClass('ethalSurveillanceIsBack');
            $listener->setFunction('checkequipement');
            $listener->setOption(array('equipement_id' => $this->getId()));
            $listener->emptyEvent();
            $listener->addEvent($this->getConfiguration('cmdequipement'));

            $listener->save();
            log::add('ethalSurveillanceIsBack', 'debug', 'Mise à jour du listener->checkequipement');
        }
    }

    /* public plugin function */

    public function ethCumulTps($_startDate = null, $_endDate = null)
    {
        $result       = [];
        $prevValue    = 0;
        $prevDatetime = 0;
        $day          = null;

        $etatCmd      = $this->getCmd(null, 'etat');
        if (!is_object($etatCmd)) {
            return $result;
        }
        foreach ($etatCmd->getHistory($_startDate, $_endDate) as $history) {
            if (date('Y-m-d', strtotime($history->getDatetime())) != $day && $prevValue == 1 && $day != null) {
                if (strtotime($day . ' 23:59:59') > $prevDatetime) {
                    $result[$day][1] += (strtotime($day . ' 23:59:59') - $prevDatetime) / 3600;
                }
                $prevDatetime = strtotime(date('Y-m-d 00:00:00', strtotime($history->getDatetime())));
            }
            $day = date('Y-m-d', strtotime($history->getDatetime()));
            if (!isset($result[$day])) {
                $result[$day] = array(strtotime($day . ' 00:00:00 UTC') * 1000, 0);
            }
            if ($history->getValue() == 1 && $prevValue == 0) {
                $prevDatetime = strtotime($history->getDatetime());
                $prevValue    = 1;
            }
            if ($history->getValue() == 0 && $prevValue == 1) {
                if ($prevDatetime > 0 && strtotime($history->getDatetime()) > $prevDatetime) {
                    $result[$day][1] += (strtotime($history->getDatetime()) - $prevDatetime) / 3600;
                }
                $prevValue = 0;
            }
        }

        return $result;
    }

    // Not used , work in progress
    public function ethCumulCpt($startDate = null, $endDate = null)
    {
        $result       = [];
        $prevValue    = 0;
        $prevDatetime = 0;
        $day          = null;

        $cptCmd       = $this->getCmd(null, 'count');
        if (!is_object($cptCmd)) {
            return $result;
        }
        foreach ($ctpCmd->getHistory($startDate, $endDate) as $history) {
            if (date('Y-m-d', strtotime($history->getDatetime())) != $day && $prevValue == 1 && $day !== null) {
                if (strtotime($day . ' 23:59:59') > $prevDatetime) {
                    $result[$day][1] += (strtotime($day . ' 23:59:59') - $prevDatetime) / 3600;
                }
                $prevDatetime = strtotime(date('Y-m-d 00:00:00', strtotime($history->getDatetime())));
            }
            $day = date('Y-m-d', strtotime($history->getDatetime()));
            if (!isset($result[$day])) {
                $result[$day] = array(strtotime($day . ' 00:00:00 UTC') * 1000, 0);
            }
            if ($history->getValue() == 1 && $prevValue == 0) {
                $prevDatetime = strtotime($history->getDatetime());
                $prevValue    = 1;
            }
            if ($history->getValue() == 0 && $prevValue == 1) {
                if ($prevDatetime > 0 && strtotime($history->getDatetime()) > $prevDatetime) {
                    $result[$day][1] += (strtotime($history->getDatetime()) - $prevDatetime) / 3600;
                }
                $prevValue = 0;
            }
        }

        return $result;
    }

}

class ethalSurveillanceIsBackCmd extends cmd
{
    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

    public function execute($_options = [])
    {
        $ethalSurveillanceIsBack = $this->getEqLogic();

        log::add('ethalSurveillanceIsBack', 'debug', 'action->' . $this->getLogicalId());

        if ($this->getLogicalId() == 'setcountplus') {
            $compteur = $ethalSurveillanceIsBack->getCmd(null, 'count')->execCmd();
            $ethalSurveillanceIsBack->checkAndUpdateCmd('count', $compteur + 1);
            log::add('ethalSurveillanceIsBack', 'debug', 'Set Compteurs plus 1');
        }
        if ($this->getLogicalId() == 'setcountmoins') {
            $compteur = $ethalSurveillanceIsBack->getCmd(null, 'count')->execCmd();
            $ethalSurveillanceIsBack->checkAndUpdateCmd('count', $compteur - 1);
            log::add('ethalSurveillanceIsBack', 'debug', 'Set Compteurs moins 1');
        }
        if ($this->getLogicalId() == 'razcount') {
            $ethalSurveillanceIsBack->checkAndUpdateCmd('count', 0);
            log::add('ethalSurveillanceIsBack', 'debug', 'RAZ Compteurs');
        }
        if ($this->getLogicalId() == 'raztempsfcttotal') {
            $ethalSurveillanceIsBack->checkAndUpdateCmd('tempsfcttotal', 0);
            $ethalSurveillanceIsBack->checkAndUpdateCmd('tempsfcttotal_hms', '-');
            $ethalSurveillanceIsBack->setConfiguration('previoustpsfct', 0);
            $ethalSurveillanceIsBack->save();
            log::add('ethalSurveillanceIsBack', 'debug', 'RAZ Temps Fct Total');
        }
        if ($this->getLogicalId() == 'razall') {
            $ethalSurveillanceIsBack->checkAndUpdateCmd('tempsfcttotal', 0);
            $ethalSurveillanceIsBack->checkAndUpdateCmd('tempsfcttotal_hms', '-');
            $ethalSurveillanceIsBack->setConfiguration('previoustpsfct', 0);
            $ethalSurveillanceIsBack->checkAndUpdateCmd('count', 0);
            $ethalSurveillanceIsBack->save();
            log::add('ethalSurveillanceIsBack', 'debug', 'RAZ All');
        }
    }

}
