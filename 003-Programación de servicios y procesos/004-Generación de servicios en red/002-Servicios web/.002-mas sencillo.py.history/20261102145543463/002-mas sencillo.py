from http.server import HTTPServer, BaseHTTPRequestHandler

def responder(self):
    self.send_response(200)
    self.end_headers()
    self.wfile.write(b"Hola Mundo")

Servidor = type("Servidor", (BaseHTTPRequestHandler,), {"do_GET": responder})

HTTPServer(("localhost", 8000), Servidor).serve_forever()