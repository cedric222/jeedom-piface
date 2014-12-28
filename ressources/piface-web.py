from BaseHTTPServer import BaseHTTPRequestHandler,HTTPServer
import urlparse
from urlparse import parse_qs
import pprint
import pifacedigitalio
import syslog
import sys
import json


DEFAULT_PORT = 8000


class GetHandler(BaseHTTPRequestHandler):
    
    def do_GET(self):
        parsed_path = urlparse.urlparse(self.path)
        query_components = parse_qs(parsed_path.query)
        #pp = pprint.PrettyPrinter(indent=4)
        #pp.pprint(query_components)
        if 'output_set' in query_components:
              digital_write = query_components['output_set'][0]
              value = query_components['value'][0]
              self.send_response(200)
              self.send_header("Content-type", "application/json")
              self.end_headers()
              syslog.syslog("doing piface digital_write "+str(digital_write)+" "+str(value))
              p.output_pins[int(digital_write)].value = int(value)
              self.wfile.write('{"STATUS":"OK"}')
        elif 'status' in parsed_path.path:
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
              prepare_json["INPUT"] = prepare_json_hash_in ;
              prepare_json["OUTPUT"] = prepare_json_hash_out ;
              json_sting = json.dumps(prepare_json)
              self.wfile.write(json_sting)
        else:
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

if __name__ == '__main__':
    p = pifacedigitalio.PiFaceDigital()
    # get the port
    if len(sys.argv) > 1:
        port = int(sys.argv[1])
    else:
        port = DEFAULT_PORT
    

    server = HTTPServer(('', port), GetHandler)
    print 'Starting server, use <Ctrl-C> to stop'
    server.serve_forever()
