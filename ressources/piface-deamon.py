#!/usr/bin/env python
import socket
import pifacedigitalio as p
import syslog

UDP_IP = "192.168.0.15"
UDP_PORT = 5006

sock = socket.socket(socket.AF_INET, # Internet
                     socket.SOCK_DGRAM) # UDP
sock.bind((UDP_IP, UDP_PORT))

p.init()

#p.digital_read_pullup(0);
#p.digital_read_pullup(1);
#p.digital_read_pullup(2);
#p.digital_read_pullup(6);
#p.digital_read_pullup(7);

while True:
        data, addr = sock.recvfrom(1024) # buffer size is 1024 bytes
        print "received message:", data
        print "read0 = "+str(p.digital_read(0)) 
        print "read1 = "+str(p.digital_read(1))
        print "read2 = "+str(p.digital_read(2))
        print "read3 = "+str(p.digital_read(3))
        print "read4 = "+str(p.digital_read(4))
        print "read5 = "+str(p.digital_read(5))
        print "read6 = "+str(p.digital_read(6))
        print "read7 = "+str(p.digital_read(7))
        #donnees = splitter.split(data)
        print data[0]
        print data[2]
        p.digital_write(int(data[0]),int(data[2]))
        syslog.syslog("doing chauf "+data[0]+" "+data[2])


