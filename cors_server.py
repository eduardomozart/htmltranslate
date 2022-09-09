#!/usr/bin/python3

import http.server as httpserver

class CORSHTTPRequestHandler(httpserver.SimpleHTTPRequestHandler):
    def send_head(self):
        """Common code for GET and HEAD commands.
        This sends the response code and MIME headers.
        Return value is either a file object (which has to be copied
        to the outputfile by the caller unless the command was HEAD,
        and must be closed by the caller under all circumstances), or
        None, in which case the caller has nothing further to do.
        """
        path = self.translate_path(self.path)
        f = None
        if os.path.isdir(path):
            if not self.path.endswith('/'):
                # redirect browser - doing basically what apache does
                self.send_response(301)
                self.send_header("Location", self.path + "/")
                self.end_headers()
                return None
            for index in "index.html", "index.htm":
                index = os.path.join(path, index)
                if os.path.exists(index):
                    path = index
                    break
            else:
                return self.list_directory(path)
        ctype = self.guess_type(path)
        try:
            # Always read in binary mode. Opening files in text mode may cause
            # newline translations, making the actual size of the content
            # transmitted *less* than the content-length!
            f = open(path, 'rb')
        except IOError:
            self.send_error(404, "File not found")
            return None
        self.send_response(200)
        self.send_header("Content-type", ctype)
        fs = os.fstat(f.fileno())
        self.send_header("Content-Length", str(fs[6]))
        self.send_header("Last-Modified", self.date_time_string(fs.st_mtime))
        self.send_header("Access-Control-Allow-Origin", "*")
        self.end_headers()
        return f

def start_server():
    handler = CORSHTTPRequestHandler

    httpd = socketserver.TCPServer(("", PORT), handler, bind_and_activate=False)
    httpd.allow_reuse_address = True
    httpd.server_bind()
    httpd.server_activate()

    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        httpd.shutdown()
        gtk.main_quit();
        #httpd.socket_close()
        exit();

def trayicon():
    indicator = appindicator.Indicator.new("customtray", "semi-starred-symbolic", appindicator.IndicatorCategory.APPLICATION_STATUS)
    indicator.set_status(appindicator.IndicatorStatus.ACTIVE)
    indicator.set_menu(menu())
    gtk.main()

def menu():
    menu = gtk.Menu()

    opentray = gtk.MenuItem('Open HTTP CORS Server')
    opentray.connect('activate', opent)
    menu.append(opentray)

    exittray = gtk.MenuItem('Exit HTTP CORS Server')
    exittray.connect('activate', quit)
    menu.append(exittray)
  
    menu.show_all()
    return menu

def opent(_):
    print(f"serving at port {PORT}")
    webbrowser.open("http://localhost:" + str(PORT))

def quit(_):
    gtk.main_quit();
    #httpd.shutdown();
    #httpd.socket_close();
    # Terminate the process
    proc.terminate()  # sends a SIGTERM
    sys.exit();

if __name__ == "__main__":
    import os
    import socketserver
    import webbrowser
    #import threading
    import multiprocessing

    import sys
    PORT = int(sys.argv[1]) if len(sys.argv) > 1 else 8000

    from gi.repository import Gtk as gtk, AppIndicator3 as appindicator # Ubuntu apt-get install gir1.2-appindicator3

    proc = multiprocessing.Process(target=start_server, args=())
    proc.start()

    print(f"serving at port {PORT}")
    webbrowser.open("http://localhost:" + str(PORT))

    trayicon()
