#!/usr/bin/python
import socket
import requests
print 'defining Host and Port'
HOST = '192.168.0.35'
PORT = 10002
print 'opening socket'
alarm = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
alarm.bind((HOST, PORT))
while 1:
	print 'Listening for Connection'
	alarm.listen(1)
	conn, addr = alarm.accept()
	print 'Connected by', addr
	while 1:
		signal = conn.recv(1024)
		print 'received ', signal
		reply = chr(6)+'\r\n'\
#		print 'sent ', reply
		conn.send(reply)
		if not signal: break
		if not signal == '+++':
			print 'curling signal.php'
			headers = {'cache-control': 'no-cache','content-type': 'application/x-www-form-urlencoded',}
			data = [('signal', signal),('IP', addr[0])]
			response = requests.post('http://192.168.0.35/signal.php', headers=headers, data=data)
			print response
	print 'Closing Connection'
	conn.close()
conn.close()
