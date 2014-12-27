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


class piface2 extends eqLogic {
  public static function callpiface2web2($ip,$port,$_url) {
           log::add('piface2', 'Debug', 'url call ='.$ip.$port) ;
           }
   public static function callpiface2web($ip,$port,$_url) {
           $url = 'http://' . $ip.':' .$port . $_url;
           log::add('piface2', 'Debug', 'url call =  '.$url);
           $ch = curl_init();
           curl_setopt_array($ch, array(
               CURLOPT_URL => $url,
               CURLOPT_HEADER => false,
               CURLOPT_RETURNTRANSFER => true
           ));
           $result = curl_exec($ch);
           if (curl_errno($ch)) {
               $curl_error = curl_error($ch);
               curl_close($ch);
               throw new Exception(__('Echec de la requete http : ', __FILE__) . $url . ' Curl error : ' . $curl_error, 404);
           }
           curl_close($ch);
           if (strpos($result, 'Error 500: Server Error') === 0 || strpos($result, 'Error 500: Internal Server Error') === 0) {
               if (strpos($result, 'Code took too long to return result') === false) {
                   throw new Exception('Echec de la commande : ' . $_url . '. Erreur : ' . $result, 500);
               }
           }
           if (is_json($result)) {
               return json_decode($result, true);
           } else {
               return $result;
           }
       }

    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */

     //Fonction lancé automatiquement toutes les minutes par jeedom
      public static function cron() {
      $mode = config::byKey('Mode', 'piface2');
      log::add('piface2', 'Debug', 'Mode =  '.$mode);
      if ($mode != "Maitre")
      {
        $piface2_path = realpath(dirname(__FILE__) . '/../../ressources/').'/piface-web.py';
        $port = config::byKey('PifacePort', 'piface2');
        $cmd = "/usr/bin/python ".$piface2_path." ".$port;
        log::add('piface2', 'info', 'verify if running  '.$piface2_path);
        exec("pgrep --full --exact '$cmd'", $pids);
        if(empty($pids)) {
          log::add('piface2', 'error', 'PID did not exist, restart');
          log::add('piface2', 'error', "start cmd = '".$cmd."'");
          $result = exec($cmd .' >> ' . log::getPathToLog('piface2') . ' 2>&1 &');
          log::add('piface2', 'debug', 'after start'.$result);
        }
        else
        {
          log::add('piface2', 'debug', "Piface '$piface2_path' is running");
        }
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

class piface2Cmd extends cmd {
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
    log::add('piface2', 'debug', 'Début fonction d\'envoi commandes piface2');
    $eqLogic = $this->getEqLogic();
    log::add('piface2', 'debug', 'instanceId = '.   $this->getConfiguration('instanceId') );
    log::add('piface2', 'debug', 'value = '.   $this->getConfiguration('class') );
    $ippiface = $eqLogic->getConfiguration('ippiface');
    log::add('piface2', 'debug', 'mode = '. $this->getType());
    log::add('piface2', 'debug', 'ippiface = '. $ippiface);
    if ($this->getType() == 'action') {
        $result = piface2::callpiface2web($eqLogic->getConfiguration('ippiface') , $eqLogic->getConfiguration('portpiface'), '/?digital_write='.$this->getConfiguration('instanceId').'&value='. $this->getConfiguration('class'));
      }
    else {
    }

    /*     * **********************Getteur Setteur*************************** */
}
}

?>
