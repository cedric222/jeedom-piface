#!/usr/bin/python

version = "1.4"
exit = 1
debug = 1

jeedom_master_ip = ''
jeedom_master_key = ''
time_between_update = 1
last_update = 0


#from BaseHTTPServer import BaseHTTPRequestHandler,HTTPServer

from http.server import BaseHTTPRequestHandler,HTTPServer
import urllib
import pprint
import pifacedigitalio
import sys
import json
import os
import sys
import http.client
from time import localtime, strftime, sleep
from datetime import datetime


pyfolder = os.path.dirname(os.path.realpath(__file__)) + "/"
LOG_FILENAME_ERR = pyfolder + '../../../log/piface2_daemon_err'
LOG_FILENAME_OUT = pyfolder + '../../../log/piface2_daemon_out'

def log(level,message):
    if level != 'debug':
      print(str(strftime("%Y-%m-%d %H:%M:%S", localtime())) + " | "+str(level)+" | " + str(message))
    elif debug:
      print(str(strftime("%Y-%m-%d %H:%M:%S", localtime())) + " | debug | " + str(message))


DEFAULT_PORT = 8000

def need_update(num,timestamp):
  # fonction qui envoi une demande d'update au serveur
  global last_update
  log ('debug',"IN need_update")
  if jeedom_master_ip == '' :
       log ('debug', "No server registred : need 1 request from master")
  elif (timestamp - last_update >= time_between_update):
        log ('debug',"Try to connect to : "+jeedom_master_ip)
        last_update = timestamp
        conn = httplib.HTTPConnection(jeedom_master_ip)
        conn.request("GET", "/jeedom/core/api/jeeApi.php?apikey="+str(jeedom_master_key)+"&type=piface2&messagetype=update")
        log ('debug',"url=/jeedom/core/api/jeeApi.php?apikey="+str(jeedom_master_key)+"&type=piface2&messagetype=update")
        r1 = conn.getresponse()
        conn.close()
        log ('debug',"end http")
  else:
        log ('debug',"too early, waiting")
  
def Interrupt_Impulsion(event):
  # fonction appelee en cas d'event
  global event_counter
  log('debug',"In Interrupt event_counter = "+ str(event_counter[event.pin_num]) + " event.pin = "+str(event.pin_num)+" interrupt_flag="+str(event.interrupt_flag)+" direction="+str(event.direction)+" chip ="+str(event.chip)+" timestamp = "+str(event.timestamp))
  if event.direction == 0:
      event_counter[event.pin_num] += 1
  need_update(event.pin_num,event.timestamp)
  listener.activate()


class GetHandler(BaseHTTPRequestHandler):
    #class pour le serveur web
    def do_GET(self):
        global jeedom_master_ip
        global jeedom_master_key 
        parsed_path = urllib.parse.urlparse(self.path)
        query_components = urllib.parse.parse_qs(parsed_path.query)
        if 'output_set' in query_components:
              # changement d etat d un output
              digital_write = query_components['output_set'][0]
              value = query_components['value'][0]
              self.send_response(200)
              self.send_header("Content-type", "application/json")
              self.end_headers()
              log('debug',"doing piface digital_write "+str(digital_write)+" "+str(value))
              p.output_pins[int(digital_write)].value = int(value)
              self.wfile.write(bytes('{"STATUS":"OK"}',"utf-8"))
        elif 'status' in parsed_path.path:
              #demande de status
              if 'apikey' in  query_components:
                  jeedom_master_key =  query_components['apikey'][0]
                  #log('debug',"find master key "+jeedom_master_key)
              if 'jeedom_master_ip' in  query_components:
                  jeedom_master_ip =  query_components['jeedom_master_ip'][0]
                  #log('debug',"find master ip "+jeedom_master_ip)
              prepare_json_hash_in = {}
              prepare_json_hash_out = {}
              for i in range(0,8):
                  prepare_json_hash_in[i] = p.input_pins[i].value
                  prepare_json_hash_out[i] = p.output_pins[i].value
              #prepare_json_hash_in[3] = 1
              self.send_response(200)
              self.send_header("Content-type", "application/json")
              self.end_headers()
              prepare_json = {}
              prepare_json["STATUS"] = "OK"
              prepare_json["VERSION"] = version
              prepare_json["INPUT"] = prepare_json_hash_in ;
              prepare_json["OUTPUT"] = prepare_json_hash_out ;
              prepare_json["EVENTS_COUNTER"] = event_counter ;
              json_sting = json.dumps(prepare_json)
              self.wfile.write(bytes(json_sting,"utf-8"))
        elif 'version' in  parsed_path.path:
              #demande de version 
              prepare_json = {}
              prepare_json["VERSION"] = version
              json_sting = json.dumps(prepare_json)
              self.send_response(200)
              self.send_header("Content-type", "application/json")
              self.end_headers()
              self.wfile.write(bytes(json_sting),"utf-8")
        elif 'exit' in  parsed_path.path:
              # todo a soft kill
              global exit
              exit = 0
              prepare_json = {}
              prepare_json["EXIT"] = "GO"
              json_sting = json.dumps(prepare_json)
              self.send_response(200)
              self.send_header("Content-type", "application/json")
              self.end_headers()
              self.wfile.write(bytes(json_sting,"utf-8"))
        else:
            # for debug
            message_parts = [
                'CLIENT VALUES:',
                'client_address=%s (%s)' % (self.client_address,
                                            self.address_string()),
                'command=%s' % self.command,
                'path=%s' % self.path,
                'real path=%s' % parsed_path.path,
                'query=%s' % parsed_path.query,
                'request_version=%s' % self.request_version,
                '',
                'SERVER VALUES:',
                'server_version=%s' % self.server_version,
                'sys_version=%s' % self.sys_version,
                'protocol_version=%s' % self.protocol_version,
                '',
                'HEADERS RECEIVED:',
                ]
            for name, value in sorted(self.headers.items()):
                message_parts.append('%s=%s' % (name, value.rstrip()))
            message_parts.append('')
            message = '\r\n'.join(message_parts)
            self.send_response(200)
            self.end_headers()
            self.wfile.write(bytes(message,"utf-8"))
        return

def run_while_true():
    """
    This assumes that keep_running() is a function of no arguments which
    is tested initially and after each request.  If its return value
    is true, the server continues.
    """
    #Test si un port specifique est passe en parametre de lancement
    if len(sys.argv) > 1:
        port = int(sys.argv[1])
    else:
        port = DEFAULT_PORT
    server = HTTPServer(('', port), GetHandler)
    while exit:
        server.handle_request()
    server.socket.close()
    


if __name__ == '__main__':
    #Creation d un pid pour pouvoir killer le daemon proprement
    pid = str(os.getpid())
    sys.stdout = open(LOG_FILENAME_OUT, 'a', 1)
    if debug:
        sys.stderr = open(LOG_FILENAME_ERR, 'a', 1)
    log('info',"start piface2 Deamon")
    if os.path.isfile("/tmp/piface-web.pid"):
        log ( 'info',"/tmp/piface-web.pid already exists, exiting" )
        sys.exit()
    else:
        pidfile = open("/tmp/piface-web.pid", mode="w", encoding="utf-8")
        pidfile.write(pid)
        pidfile.close()
	
    #Initialisation du Json pour les EVENT
     
    event_counter  = {}
    for i in range(0,8):
        event_counter[i] = 0
    #Initialisation de la carte PiFace
    p = pifacedigitalio.PiFaceDigital()
    listener = pifacedigitalio.InputEventListener(chip=p)
    #Boucle pour declarer toutes les inputs en interuption
    for i in range(0,8):
        listener.register(i, pifacedigitalio.IODIR_ON, Interrupt_Impulsion)
        log ('debug',"register "+str(i))
    listener.activate()
    try:  
        run_while_true()
    except:
        log ('info',"end in except")
    #Kill
    #listener.deactivate()
    log ('info',"Bye.")

