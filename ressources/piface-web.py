from BaseHTTPServer import BaseHTTPRequestHandler,HTTPServer
import urlparse
from urlparse import parse_qs
import pprint
import pifacedigitalio as p
import syslog
import sys


DEFAULT_PORT = 8000


class GetHandler(BaseHTTPRequestHandler):
    
    def do_GET(self):
        parsed_path = urlparse.urlparse(self.path)
        query_components = parse_qs(parsed_path.query)
        #pp = pprint.PrettyPrinter(indent=4)
        #pp.pprint(query_components)
        if 'digital_write' in query_components:
              digital_write = query_components['digital_write'][0]
              value = query_components['value'][0]
              self.send_response(200)
              self.send_header("Content-type", "application/json")
              self.end_headers()
              syslog.syslog("doing piface digital_write "+str(digital_write)+" "+str(value))
              p.digital_write(int(digital_write),int(value))
              self.wfile.write('{"ANSWER":"OK"}')
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
    p.init()
    # get the port
    if len(sys.argv) > 1:
        port = int(sys.argv[1])
    else:
        port = DEFAULT_PORT
    

    server = HTTPServer(('', port), GetHandler)
    print 'Starting server, use <Ctrl-C> to stop'
    server.serve_forever()
