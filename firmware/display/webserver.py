#!/usr/bin/env python3

import os

# Python 2
#from BaseHTTPServer import BaseHTTPRequestHandler, HTTPServer
#from urlparse import urlparse

# Python 3
from http.server import BaseHTTPRequestHandler, HTTPServer
from urllib.parse import urlparse
 
class StaticServer(BaseHTTPRequestHandler):
 
    def do_GET(self):
        root = os.path.dirname(os.path.abspath(__file__)) + '/www'
        #print root

        if self.path == '/':
            filename = root + '/index.html'
        else:
            parsed = urlparse(self.path)        
            filename = root + parsed.path
 
        self.send_response(200)
        
        if filename[-4:] == '.css':
           self.send_header('Content-type', 'text/css')
        elif filename[-5:] == '.json':
           self.send_header('Content-type', 'application/json')
        elif filename[-3:] == '.js':
           self.send_header('Content-type', 'application/javascript')
        elif filename[-4:] == '.ico':
           self.send_header('Content-type', 'image/x-icon')
        elif filename[-4:] == '.jpg':
           self.send_header('Content-type', 'image/jpg')
        elif filename[-4:] == '.png':
           self.send_header('Content-type', 'image/png')            
        else:
            self.send_header('Content-type', 'text/html')
        self.end_headers()
        
        with open(filename, 'rb') as fh:
            html = fh.read()
            #html = bytes(html, 'utf8')
            self.wfile.write(html)
 
def run(server_class=HTTPServer, handler_class=StaticServer, port=80):
    server_address = ('', port)
    httpd = server_class(server_address, handler_class)
    print('Starting httpd on port {}'.format(port))
    httpd.serve_forever()
 
run()
