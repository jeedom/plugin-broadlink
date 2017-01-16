# -*- coding: utf-8 -*-
# This file is part of Jeedom.
#
# Jeedom is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# Jeedom is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with Jeedom. If not, see <http://www.gnu.org/licenses/>.

import logging
import string
import sys
import os
import time
import datetime
import binascii
import struct
import threading
from threading import Thread, Event, Timer
import re
import signal
import argparse
import traceback
from os.path import join
import json
from broadlink import broadlink,rm2,a1,mp1
import globals

try:
	from jeedom.jeedom import *
except ImportError:
	print "Error: importing module from jeedom folder"
	sys.exit(1)


# ----------------------------------------------------------------------------

def listen():
	logging.debug("Start listening...")
	jeedom_socket.open()
	try:
		while 1:
			time.sleep(0.02)
			read_socket()
			read_broadlink()
	except KeyboardInterrupt:
		shutdown()

def read_socket():
	try:
		global JEEDOM_SOCKET_MESSAGE
		if not JEEDOM_SOCKET_MESSAGE.empty():
			logging.debug("Message received in socket JEEDOM_SOCKET_MESSAGE")
			message = json.loads(jeedom_utils.stripped(JEEDOM_SOCKET_MESSAGE.get()))
			if message['apikey'] != _apikey:
				logging.error("Invalid apikey from socket : " + str(message))
				return
			if message['cmd'] == 'add':
				logging.debug('Add device : '+str(message['device']))
				if 'mac' in message['device']:
					globals.KNOWN_DEVICES[message['device']['mac']] = message['device']
			elif message['cmd'] == 'remove':
				logging.debug('Remove device : '+str(message['device']))
				if 'mac' in message['device']:
					del globals.KNOWN_DEVICES[message['device']['mac']]
			elif message['cmd'] == 'learnin':
				logging.debug('Enter in learn mode')
				globals.LEARN_MODE = True
				jeedom_com.send_change_immediate({'learn_mode' : 1});
				devices = broadlink.discover(timeout=5)
				logging.debug("found " + str(devices))
				globals.LEARN_MODE = False
				jeedom_com.send_change_immediate({'learn_mode' : 0});
				for device in devices:
					type = device.type
					ip = device.host[0]
					port = device.host[1]
					mac = binascii.hexlify(bytearray(device.mac))
					jeedom_com.add_changes('devices::'+mac,{'type' : type, 'ip' : ip , 'mac': mac, 'port' : port, 'learn' : 1})
			elif message['cmd'] == 'send':
				if 'mac' in message['device']:
					logging.debug('Send command')
					send_broadlink(message)
	except Exception,e:
		logging.error(str(e))
# ----------------------------------------------------------------------------
def read_broadlink():
	now = datetime.datetime.utcnow()
	result = {}
	try:
		for device in globals.KNOWN_DEVICES:
			mac = globals.KNOWN_DEVICES[device]['mac']
			if mac in globals.LAST_TIME_READ and now < (globals.LAST_TIME_READ[mac]+datetime.timedelta(milliseconds=int(globals.KNOWN_DEVICES[device]['delay'])*1000)):
				continue
			else :
				globals.LAST_TIME_READ[mac] = now
				if globals.KNOWN_DEVICES[device]['type'] == 'rm2':
					logging.debug('Handling RM2 for ' + globals.KNOWN_DEVICES[device]['name'])
					result = rm2.read_rm2(globals.KNOWN_DEVICES[device])
				elif globals.KNOWN_DEVICES[device]['type'] == 'a1':
					logging.debug('Handling A1 for ' + globals.KNOWN_DEVICES[device]['name'])
					result = a1.read_a1(globals.KNOWN_DEVICES[device])
				if result :
					if mac in globals.LAST_STATE and result == globals.LAST_STATE[mac]:
						continue
					else:
						globals.LAST_STATE[mac] = result
						jeedom_com.add_changes('devices::'+mac,result)
	except Exception,e:
		if str(e) == 'timed out':
			logging.debug('Device seems offline')
		else:
			logging.error(str(e))
	
# ----------------------------------------------------------------------------

def send_broadlink(message):
	result = {}
	if message['cmdType'] == 'refresh':
		if message['device']['type'] == 'rm2':
			result = rm2.read_rm2(message['device'])
		elif message['device']['type'] == 'a1':
			result = a1.read_a1(message['device'])
		elif message['device']['type'] == 'mp1':
			result = mp1.read_mp1(message['device'])
		if result :
			if message['device']['mac'] in globals.LAST_STATE and result == globals.LAST_STATE[message['device']['mac']]:
				return
			else:
				globals.LAST_STATE[message['device']['mac']] = result
				jeedom_com.add_changes('devices::'+message['device']['mac'],result)
		return
	elif message['device']['type'] == 'rm2':
		if message['cmdType'] == 'learn':
			result = rm2.learn_rm2(message['device'])
			if result:
				jeedom_com.add_changes('devices::'+message['device']['mac'],result)
		else:
			rm2.send_rm2(message['device'])
	elif message['device']['type'] == 'mp1':
		result = mp1.send_mp1(message['device'])
		if result:
			jeedom_com.add_changes('devices::'+message['device']['mac'],result)
	return


# ----------------------------------------------------------------------------

def handler(signum=None, frame=None):
	logging.debug("Signal %i caught, exiting..." % int(signum))
	shutdown()

def shutdown():
	logging.debug("Shutdown")
	logging.debug("Removing PID file " + str(_pidfile))
	try:
		os.remove(_pidfile)
	except:
		pass
	try:
		jeedom_socket.close()
	except:
		pass
	try:
		globals.JEEDOM_SERIAL.close()
	except:
		pass
	logging.debug("Exit 0")
	sys.stdout.flush()
	os._exit(0)

# ----------------------------------------------------------------------------

_log_level = "error"
_socket_port = 55013
_socket_host = '127.0.0.1'
_pidfile = '/tmp/broadlinkd.pid'
_apikey = ''
_callback = ''
_cycle = 0.3;

parser = argparse.ArgumentParser(description='Broadlink Daemon for Jeedom plugin')
parser.add_argument("--socketport", help="Socketport for server", type=str)
parser.add_argument("--sockethost", help="Sockethost for server", type=str)
parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
parser.add_argument("--callback", help="Callback", type=str)
parser.add_argument("--apikey", help="Apikey", type=str)
parser.add_argument("--cycle", help="Cycle to send event", type=str)
args = parser.parse_args()

if args.socketport:
	_socket_port = int(args.socketport)
if args.loglevel:
	_log_level = args.loglevel
if args.callback:
	_callback = args.callback
if args.apikey:
	_apikey = args.apikey
if args.cycle:
	_cycle = float(args.cycle)

jeedom_utils.set_log_level(_log_level)

logging.info('Start broadlinkd')
logging.info('Log level : '+str(_log_level))
logging.info('Socket port : '+str(_socket_port))
logging.info('Socket host : '+str(_socket_host))
logging.info('PID file : '+str(_pidfile))
logging.info('Apikey : '+str(_apikey))
logging.info('Callback : '+str(_callback))
logging.info('Cycle : '+str(_cycle))

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)

try:
	jeedom_utils.write_pid(str(_pidfile))
	jeedom_com = jeedom_com(apikey = _apikey,url = _callback,cycle=_cycle)
	if not jeedom_com.test():
		logging.error('Network communication issues. Please fixe your Jeedom network configuration.')
		shutdown()
	jeedom_socket = jeedom_socket(port=_socket_port,address=_socket_host)
	listen()
except Exception,e:
	logging.error('Fatal error : '+str(e))
	logging.debug(traceback.format_exc())
	shutdown()
