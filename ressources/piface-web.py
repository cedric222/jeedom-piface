#! /usr/bin/python

version = "1.2"
exit = 1

jeedom_master_ip = ''
jeedom_master_key = ''
time_between_update = 10
last_update = 0


from BaseHTTPServer import BaseHTTPRequestHandler,HTTPServer
import urlparse
from urlparse import parse_qs
import pprint
import pifacedigitalio
import syslog
import sys
import json
import os
import sys
import httplib



DEFAULT_PORT = 8000

def need_update(num,timestamp):
  # fonction qui envoi une demande d'update au serveur
  global last_update
  
  if jeedom_master_ip == '' :
       print "No server registred"
  elif (timestamp - last_update >= time_between_update):
	last_update = timestamp
	conn = httplib.HTTPConnection(jeedom_master_ip)
	conn.request("GET", "/jeedom/core/api/jeeApi.php?apikey="+str(jeedom_master_key)+"&type=piface2&messagetype=update")
        r1 = conn.getresponse()
	#print r1.status, r1.reason
	conn.close()
  else:
  	print "too early, waiting"
  
def Interrupt_Impulsion(event):
  # fonction appelee en cas d'event
  global event_counter
  if event.direction == 0:
      event_counter[event.pin_num] += 1
      need_update(event.pin_num,event.timestamp)
  syslog.syslog("event_counter = "+ str(event_counter[event.pin_num]) + " event.pin = "+str(event.pin_num)+" interrupt_flag="+str(event.interrupt_flag)+" direction="+str(event.direction)+" chip ="+str(event.chip)+" timestamp = "+str(event.timestamp))


class GetHandler(BaseHTTPRequestHandler):
    #class pour le serveur web
    def do_GET(self):
        global jeedom_master_ip
        global jeedom_master_key 
        parsed_path = urlparse.urlparse(self.path)
        query_components = parse_qs(parsed_path.query)
        if 'output_set' in query_components:
              # changement d etat d un output
              digital_write = query_components['output_set'][0]
              value = query_components['value'][0]
              self.send_response(200)
              self.send_header("Content-type", "application/json")
              self.end_headers()
              syslog.syslog("doing piface digital_write "+str(digital_write)+" "+str(value))
              p.output_pins[int(digital_write)].value = int(value)
              self.wfile.write('{"STATUS":"OK"}')
        elif 'status' in parsed_path.path:
              #demande de status
              if 'apikey' in  query_components:
                  jeedom_master_key =  query_components['apikey'][0]
              if 'jeedom_master_ip' in  query_components:
                  jeedom_master_ip =  query_components['jeedom_master_ip'][0]
              prepare_json_hash_in = {}
              prepare_json_hash_out = {}
              for i in range(0,8):
                  prepare_json_hash_in[i] = p.input_pins[i].value
                  prepare_json_hash_out[i] = p.output_pins[i].value
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
              self.wfile.write(json_sting)
        elif 'version' in  parsed_path.path:
              #demande de version 
              prepare_json = {}
              prepare_json["VERSION"] = version
              json_sting = json.dumps(prepare_json)
              self.send_response(200)
              self.send_header("Content-type", "application/json")
              self.end_headers()
              self.wfile.write(json_sting)
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
              self.wfile.write(json_sting)
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
            self.wfile.write(message)
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
    pidfile = "/tmp/piface-web.pid"
    if os.path.isfile(pidfile):
        print "%s already exists, exiting" % pidfile
        sys.exit()
    else:
        file(pidfile, 'w').write(pid)
    #Initialisation du Json pour les EVENT
    event_counter  = {}
    for i in range(0,8):
        event_counter[i] = 0
    #Initialisation de la carte PiFace
    p = pifacedigitalio.PiFaceDigital()
    listener = pifacedigitalio.InputEventListener(chip=p)
    #Boucle pour declarer toutes les inputs en interuption
    for i in range(0,8):
	    listener.register(i, pifacedigitalio.IODIR_BOTH, Interrupt_Impulsion)
    listener.activate()
    try:  
        run_while_true()
    except:
        print "end in except"
    #Kill
    listener.deactivate()
    os.unlink(pidfile)
    print "Bye."

