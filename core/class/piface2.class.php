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
      log::add('piface2', 'Debug', 'In cron in Mode =  '.$mode);
      if (!self::deamonRunning()) {
           self::runDeamon();
      }
      if ($mode != "esclave")
            {
            self::update_info();
            }
      }

      public static function update_info()
       {
      foreach (eqLogic::byType('piface2') as $eqLogic) {
           if ($eqLogic->getIsEnable() == 1) {
             $result = piface2::callpiface2web($eqLogic->getConfiguration('ippiface') , $eqLogic->getConfiguration('portpiface'), '/status');
             foreach ($eqLogic->getCmd() as $cmd) {
                log::add('piface2', 'debug', 'in for instanceId = '.   $cmd->getConfiguration('instanceId') );
                log::add('piface2', 'debug', 'in for interface = '.   $cmd->getConfiguration('interface') );
                log::add('piface2', 'debug', 'getType = '.   $cmd->getType() );
                $piface_type = strtoupper( $cmd->getConfiguration('interface'));
                if ( $cmd->getType() == 'info' and 
                    (  $piface_type == 'INPUT' or $piface_type == 'OUTPUT' or $piface_type == 'EVENTS_COUNTER'))
                          {
                           $cmd->event($result[$piface_type][$cmd->getConfiguration('instanceId')]);
                           log::add('piface2', 'debug', 'set = '.   $result[$piface_type][$cmd->getConfiguration('instanceId')] );
                          }
                      }
            }
           }
      }
    public static function runDeamon() {
      log::add('piface2', 'debug', 'runDeamon');
      if (config::byKey('Mode', 'piface2') != "maitre")
        {
         $piface2_path = realpath(dirname(__FILE__) . '/../../ressources/').'/piface-web.py';
         $port = config::byKey('PifacePort', 'piface2');
         $cmd = "/usr/bin/python ".$piface2_path." ".$port;
        $result = exec($cmd . ' >> ' . log::getPathToLog('piface2') . ' 2>&1 &');
        if (strpos(strtolower($result), 'error') !== false || strpos(strtolower($result), 'traceback') !== false) {
            log::add('piface2', 'error', $result);
            return false;
        }
        sleep(5);
        if (!self::deamonRunning()) {
            sleep(20);
            if (!self::deamonRunning()) {
                log::add('piface2', 'error', 'Impossible de lancer le démon');
                return false;
            }
        }
        log::add('piface2', 'info', 'Démon Piface lancé');
        }
        }

        
    public static function deamonRunning() {
        log::add('piface2', 'debug', 'begin deamonRunning');
        $pid_file = '/tmp/piface-web.pid';
        if (!file_exists($pid_file)) {
            return false;
        }
        if (posix_getsid(trim(file_get_contents($pid_file)))) {
            return true;
        } else {
            unlink($pid_file);
            return false;
        }
    }
    public static function stopDeamon() {
        log::add('piface2', 'debug', 'begin stopDeamon');
        if (!self::deamonRunning()) {
            return true;
        }
        $pid_file = '/tmp/piface-web.pid';
        if (!file_exists($pid_file)) {
            return true;
        }
        $pid = intval(trim(file_get_contents($pid_file)));
        $kill = posix_kill($pid, 15);
        $retry = 0;
        while (!$kill && $retry < 5) {
            sleep(1);
            $kill = posix_kill($pid, 9);
            $retry++;
        }
        if (self::deamonRunning()) {
            sleep(1);
            exec('kill -9 ' . $pid . ' > /dev/null 2&1');
            sleep(1);
            exec('kill -9 ' . $pid . ' > /dev/null 2&1');
        } else {
            if (file_exists($pid_file)) {
                unlink($pid_file);
            }
        }
        return self::deamonRunning();
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
    self::runDeamon();   
    }

    public function preSave() {
    log::add('piface2', 'debug', 'in preSave');
        
    }

    public function postSave() {
    log::add('piface2', 'debug', 'in postSave');
    self::stopDeamon();
    self::runDeamon();
        
    }

    public function preUpdate() {
        
    }

    public function postUpdate() {
    log::add('piface2', 'debug', 'in postUpdate');
        self::stopDeamon();
        self::runDeamon();
        
    }

    public function preRemove() {
    log::add('piface2', 'debug', 'in preRemove');
        self::stopDeamon();
        
    }

    public function postRemove() {
    log::add('piface2', 'debug', 'in post Remove');
        self::stopDeamon();
        
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
        log::add('piface2', 'debug', 'in execute with instanceId = '.   $this->getConfiguration('instanceId').  'and value = '.   $this->getConfiguration('value') );
        if ($this->getType() == 'action') {
              $result = piface2::callpiface2web($eqLogic->getConfiguration('ippiface') , 
                                                $eqLogic->getConfiguration('portpiface'), 
                                                '/?output_set='.$this->getConfiguration('instanceId').'&value='. $this->getConfiguration('value'));
              //TODO mettre a jour les infos
               piface2::update_info();
        }
        else {
          log::add('piface2', 'debug', 'in execute with type '.   $this->getType() );
          //TODO gerer quand c'est pas de type evenement
        }

    /*     * **********************Getteur Setteur*************************** */
}
}

?>
