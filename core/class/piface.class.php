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


class piface extends eqLogic {
    /*     * *************************Attributs****************************** */



    /*     * ***********************Methode static*************************** */

     //Fonction lancé automatiquement toutes les minutes par jeedom
      public static function cron() {
      $piface_path = realpath(dirname(__FILE__) . '/../../ressources/');
      log::add('piface', 'error', 'serech  '."/usr/bin/python ".$piface_path."/chauf.py");
      exec("pgrep --full --exact '/usr/bin/python ".$piface_path."/chauf.py'", $pids);
        if(empty($pids)) {
         log::add('piface', 'error', 'PID chauf inexistant');
         $cmd = "/usr/bin/python ".$piface_path."/chauf.py";
         log::add('piface', 'error', 'start '.$cmd);
         $result = exec($cmd .' >> ' . log::getPathToLog('piface') . ' 2>&1 &');
          log::add('piface', 'error', 'after start'.$result);
        }
        else
        {
         log::add('piface', 'error', 'PID chauf is running');

        }

      }


    /*
     * Fonction lancé automatiquement toutes les heures par jeedom
      public static function cronHourly() {

      }
     */

    /*
     * Fonction lancé automatiquement touts les jours par jeedom
      public static function cronDayly() {

      }
     */



    /*     * *********************Methode d'instance************************* */

    public function preInsert() {
        
    }

    public function postInsert() {
        
    }

    public function preSave() {
        
    }

    public function postSave() {
        
    }

    public function preUpdate() {
        
    }

    public function postUpdate() {
        
    }

    public function preRemove() {
        
    }

    public function postRemove() {
        
    }

    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
      public function toHtml($_version = 'dashboard') {

      }
     */

    /*     * **********************Getteur Setteur*************************** */
}

class pifaceCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes meme si elle ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

    public function execute($_options = array()) {
        
    }

    /*     * **********************Getteur Setteur*************************** */
}

?>
