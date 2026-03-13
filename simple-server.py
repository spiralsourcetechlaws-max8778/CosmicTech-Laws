import http.server
import socketserver
import os

PORT = 8080
DIRECTORY = "."

class Handler(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=DIRECTORY, **kwargs)
    
    def end_headers(self):
        self.send_header('Access-Control-Allow-Origin', '*')
        super().end_headers()

os.chdir(DIRECTORY)
with socketserver.TCPServer(("", PORT), Handler) as httpd:
    print(f"Serving at http://102.2.220.165:{PORT}")
    print(f"Local: http://127.0.0.1:{PORT}")
    httpd.serve_forever()
