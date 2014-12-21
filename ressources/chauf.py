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

while True:
        data, addr = sock.recvfrom(1024) # buffer size is 1024 bytes
        print "received message:", data
        #donnees = splitter.split(data)
        print data[0]
        print data[2]
        p.digital_write(int(data[0]),int(data[2]))
        syslog.syslog("doing chauf "+data[0]+" "+data[2])


