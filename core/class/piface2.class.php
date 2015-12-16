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
    $ch = curl_init();
    curl_setopt_array($ch, array(
          CURLOPT_URL => $url,
          CURLOPT_HEADER => false,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_TIMEOUT => 5
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
    if (!self::deamonRunning()) {
      self::runDeamon();
    }
    if ($mode != "esclave")
    {
      log::add('piface2', 'debug', 'Start Update Info from cron');
      self::update_info();
    }
  }


  public static function update_info()
  {
    $result = '';
    $last_serv = '';
    log::add('piface2', 'debug', 'Start Update Info');
    foreach (eqLogic::byType('piface2') as $eqLogic) {
      if ($result == '' or $last_serv != $eqLogic->getConfiguration('ippiface')."_".$eqLogic->getConfiguration('portpiface'))
      {
        $result = piface2::callpiface2web($eqLogic->getConfiguration('ippiface') , $eqLogic->getConfiguration('portpiface'), '/status?apikey='.config::byKey('api').'&jeedom_master_ip='.config::byKey('internalAddr'));
        if ($result["VERSION"] == "1.4")
        {	
          log::add('piface2', 'debug', 'good deamon version '.$result["VERSION"]);
        }
        else
        {
          log::add('piface2', 'error', 'BAD DEAMON VERSION : '.$result["VERSION"]);
          self::soft_kill($eqLogic);
          self::runDeamon();
          return;
        }
        $last_serv = $eqLogic->getConfiguration('ippiface')."_".$eqLogic->getConfiguration('portpiface');
      }
      if ($eqLogic->getIsEnable() == 1) {
        foreach ($eqLogic->getCmd() as $cmd) {
          $piface_type = strtoupper( $cmd->getConfiguration('interface'));
          if ( $cmd->getType() == 'info' and 
              (  $piface_type == 'INPUT' or $piface_type == 'OUTPUT' or $piface_type == 'EVENTS_COUNTER'))
          {
#$cmd->setEventOnly(1);
            $value = $result[$piface_type][$cmd->getConfiguration('instanceId')] ;
            if ($value != $cmd->execCmd()) {
              log::add('piface2', 'debug', 'set '.$piface_type.' instanceID '.$cmd->getConfiguration('instanceId').' =  '.   $result[$piface_type][$cmd->getConfiguration('instanceId')] );
              $cmd->setCollectDate('');
              $cmd->event($value);
            }
            else
            {
              log::add('piface2', 'debug', 'dont need to change '.$piface_type.' instanceID '.$cmd->getConfiguration('instanceId').' =  '.   $result[$piface_type][$cmd->getConfiguration('instanceId')] );
            }
          }
        }
      }
    }
  }
  public static function soft_kill($eqLogic)
  {
    log::add('piface2', 'error', 'soft reset of the client');
    $result = piface2::callpiface2web($eqLogic->getConfiguration('ippiface') , $eqLogic->getConfiguration('portpiface'), '/exit');
    if ($result["EXIT"] == "OK")
    { return true ;}
    else
    {
      return false;
    }
  }
  public static function runDeamon() {
    log::add('piface2', 'debug', 'runDeamon');
    if (config::byKey('Mode', 'piface2') != "maitre")
    {
      $piface2_path = realpath(dirname(__FILE__) . '/../../ressources/').'/piface-web.py';
      $port = config::byKey('PifacePort', 'piface2');
      $cmd = "/usr/bin/nice -n 19 /usr/bin/python3 ".$piface2_path." ".$port;
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
    if (self::deamonRunning()) {
      $piface2_path = realpath(dirname(__FILE__) . '/../../ressources/').'/piface-web.py';
      $port = config::byKey('PifacePort', 'piface2');
      $cmd = "/usr/bin/python ".$piface2_path." ".$port;
      log::add('piface2', 'debug', 'verify if running  '.$piface2_path);
      exec("pgrep --full 'piface-web.py'", $pids);
      foreach ($pids as $pid)
      {
        log::add('piface2', 'info', 'stopDeamon using kill -9 '  . $pid);
        sleep(1);
        exec('kill -9 ' . $pid . ' > /dev/null 2&1');
      }
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
  }

  public function preSave() {
    log::add('piface2', 'debug', 'in preSave v1');
  }

  public function postSave() {
    log::add('piface2', 'debug', 'in postSave');
  }

  public function preUpdate() {
    log::add('piface2', 'debug', 'in preUpdate');
  }

  public function postUpdate() {
    log::add('piface2', 'debug', 'in postUpdate');
  }

  public function preRemove() {
    log::add('piface2', 'debug', 'in preRemove');
    self::stopDeamon();

  }

  public function postRemove() {
    log::add('piface2', 'debug', 'in post Remove');
    self::stopDeamon();

  }
  public static function event() {
    $messageType = init('messagetype');
    log::add('piface2', 'debug', 'in event messtype = '.$messageType);
    self::update_info();

  }
  public  static function pull($_options) {
    log::add('piface2', 'debug', 'in pull');
  }


  /*
   * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
   public function toHtml($_version = 'dashboard') {

   }
   */

  /*     * **********************Getteur Setteur*************************** */
}

class piface2Cmd extends cmd {
  public function preSave() {
    log::add('piface2', 'debug', 'in preSave V2');
    $this->setEventOnly(1);
  }
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
    $eqLogic = $this->getEqLogic();
    log::add('piface2', 'debug', 'in execute with action = '.$this->getType().', instanceId = '.   $this->getConfiguration('instanceId').  ',value = '.   $this->getConfiguration('value') );
    if ($this->getType() == 'action') {
      $result = piface2::callpiface2web($eqLogic->getConfiguration('ippiface') , 
          $eqLogic->getConfiguration('portpiface'), 
          '/?output_set='.$this->getConfiguration('instanceId').'&value='. $this->getConfiguration('value'));
      //TODO mettre a jour les infos
      log::add('piface2', 'debug', 'Start Update Info from execute');
      piface2::update_info();
    }
    else {
      log::add('piface2', 'info', 'NOT NORMAL in execute with type '.   $this->getType() );
      //TODO gerer quand c'est pas de type evenement
    }

    /*     * **********************Getteur Setteur*************************** */
  }
}

?>
