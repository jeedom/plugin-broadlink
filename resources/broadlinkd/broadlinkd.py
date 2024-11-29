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
import sys
import os
import time
import datetime
import signal
import argparse
import traceback
import json
from broadlink import broadlink,rm2,a1,mp1,sp2,rm4,lb1
import globals

from jeedom.jeedom import *

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
			message = JEEDOM_SOCKET_MESSAGE.get().decode('utf-8')
			message =json.loads(message)
			if message['apikey'] != _apikey:
				logging.error("Invalid apikey from socket: %s", message)
				return
			if message['cmd'] == 'add':
				logging.debug('Add device : %s', message['device'])
				if 'mac' in message['device']:
					globals.KNOWN_DEVICES[message['device']['mac']] = message['device']
			elif message['cmd'] == 'remove':
				logging.debug('Remove device : %s', message['device'])
				if 'mac' in message['device']:
					del globals.KNOWN_DEVICES[message['device']['mac']]
			elif message['cmd'] == 'learnin':
				logging.debug('Enter in learn mode')
				globals.LEARN_MODE = True
				globals.JEEDOMCOM.send_change_immediate({'learn_mode' : 1})
				devices = broadlink.discover(timeout=5)
				logging.debug("found %s", devices)
				globals.LEARN_MODE = False
				globals.JEEDOMCOM.send_change_immediate({'learn_mode' : 0})
				for device in devices:
					type = device.type
					devtype = device.devtype
					ip = device.host[0]
					port = device.host[1]
					mac = device.mac.hex()
					reversemac = "".join(reversed([mac[i:i+2] for i in range(0, len(mac), 2)]))
					globals.JEEDOMCOM.add_changes('devices::'+mac,{'type' : type, 'ip' : ip , 'mac': mac,'reversemac': reversemac, 'port' : port, 'learn' : 1 ,'devtype' :devtype})
			elif message['cmd'] == 'send':
				if 'mac' in message['device']:
					logging.debug('Send command')
					send_broadlink(message)
	except Exception as ex:
		logging.error(ex)
# ----------------------------------------------------------------------------
def read_broadlink():
	now = datetime.datetime.now(datetime.timezone.utc)
	result = {}
	try:
		for device in globals.KNOWN_DEVICES:
			mac = globals.KNOWN_DEVICES[device]['mac']
			if mac in globals.LAST_TIME_READ and now < (globals.LAST_TIME_READ[mac]+datetime.timedelta(milliseconds=int(globals.KNOWN_DEVICES[device]['delay'])*1000)):
				continue
			if mac[-3:] == 'sub':
				continue
			else :
				globals.LAST_TIME_READ[mac] = now
				if globals.KNOWN_DEVICES[device]['type'] == 'rm2':
					logging.debug('Handling RM2 for ' + globals.KNOWN_DEVICES[device]['name'])
					result = rm2.read_rm2(globals.KNOWN_DEVICES[device])
				elif globals.KNOWN_DEVICES[device]['type'] == 'a1':
					logging.debug('Handling A1 for ' + globals.KNOWN_DEVICES[device]['name'])
					result = a1.read_a1(globals.KNOWN_DEVICES[device])
				elif globals.KNOWN_DEVICES[device]['type'] == 'sp2':
					logging.debug('Handling SP2 for ' + globals.KNOWN_DEVICES[device]['name'])
					result = sp2.read_sp2(globals.KNOWN_DEVICES[device])
				elif globals.KNOWN_DEVICES[device]['type'] == 'mp1':
					logging.debug('Handling MP1 for ' + globals.KNOWN_DEVICES[device]['name'])
					result = mp1.read_mp1(globals.KNOWN_DEVICES[device])
				elif globals.KNOWN_DEVICES[device]['type'] == 'rm4':
					logging.debug('Handling RM4 for ' + globals.KNOWN_DEVICES[device]['name'])
					result = rm4.read_rm4(globals.KNOWN_DEVICES[device])
				elif globals.KNOWN_DEVICES[device]['type'] == 'lb1':
					logging.debug('Handling LB1 for ' + globals.KNOWN_DEVICES[device]['name'])
					result = lb1.read_lb1(globals.KNOWN_DEVICES[device])
				if result :
					if mac in globals.LAST_STATE and result == globals.LAST_STATE[mac]:
						continue
					else:
						globals.LAST_STATE[mac] = result
						globals.JEEDOMCOM.add_changes('devices::'+mac,result)
	except Exception as ex:
		if str(ex) == 'timed out':
			logging.debug('Device seems offline')
		else:
			logging.error(ex)

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
		elif message['device']['type'] == 'sp2':
			result = sp2.read_sp2(message['device'])
		elif message['device']['type'] == 'rm4':
			result = rm4.read_rm4(message['device'])
		elif message['device']['type'] == 'lb1':
			result = lb1.read_lb1(message['device'])
		if result :
			if message['device']['mac'] in globals.LAST_STATE and result == globals.LAST_STATE[message['device']['mac']]:
				return
			else:
				globals.LAST_STATE[message['device']['mac']] = result
				globals.JEEDOMCOM.add_changes('devices::'+message['device']['mac'],result)
		return
	elif message['device']['type'] == 'rm2':
		if message['cmdType'] == 'learn':
			result = rm2.learn_rm2(message['device'])
			if result:
				globals.JEEDOMCOM.add_changes('devices::'+message['device']['mac'],result)
		else:
			rm2.send_rm2(message['device'])
	elif message['device']['type'] == 'rm4':
		if message['cmdType'] == 'learn':
			result = rm4.learn_rm4(message['device'])
			if result:
				globals.JEEDOMCOM.add_changes('devices::'+message['device']['mac'],result)
		else:
			rm4.send_rm4(message['device'])
	elif message['device']['type'] == 'mp1':
		result = mp1.send_mp1(message['device'])
		if result:
			globals.JEEDOMCOM.add_changes('devices::'+message['device']['mac'],result)
	elif message['device']['type'] == 'sp2':
		result = sp2.send_sp2(message['device'])
		if result:
			globals.JEEDOMCOM.add_changes('devices::'+message['device']['mac'],result)
	elif message['device']['type'] == 'lb1':
		result = lb1.send_lb1(message['device'])
		if result:
			globals.JEEDOMCOM.add_changes('devices::'+message['device']['mac'],result)
	return


# ----------------------------------------------------------------------------

def handler(signum=None, frame=None):
	logging.debug("Signal %i caught, exiting...", signum)
	shutdown()

def shutdown():
	logging.debug("Shutdown")
	logging.debug("Removing PID file %s", _pidfile)
	try:
		os.remove(_pidfile)
	except:
		pass
	try:
		jeedom_socket.close()
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
_cycle = 0.3

parser = argparse.ArgumentParser(description='Broadlink Daemon for Jeedom plugin')
parser.add_argument("--socketport", help="Socketport for server", type=str)
parser.add_argument("--sockethost", help="Sockethost for server", type=str)
parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
parser.add_argument("--callback", help="Callback", type=str)
parser.add_argument("--apikey", help="Apikey", type=str)
parser.add_argument("--cycle", help="Cycle to send event", type=str)
parser.add_argument("--pid", help="Pid file", type=str)
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
if args.pid:
	_pidfile = args.pid

jeedom_utils.set_log_level(_log_level)

logging.info('Start broadlinkd')
logging.info('Log level: %s', _log_level)
logging.info('Socket port: %s', _socket_port)
logging.info('Socket host: %s', _socket_host)
logging.info('PID file: %s', _pidfile)
logging.info('Callback: %s', _callback)
logging.info('Cycle: %s', _cycle)

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)

try:
	jeedom_utils.write_pid(_pidfile)
	globals.JEEDOMCOM = jeedom_com(apikey = _apikey,url = _callback,cycle=_cycle)
	if not globals.JEEDOMCOM.test():
		logging.error('Network communication issues. Please fix your Jeedom network configuration.')
		shutdown()
	jeedom_socket = jeedom_socket(port=_socket_port,address=_socket_host)
	listen()
except Exception as ex:
	logging.error('Fatal error : %s', ex)
	logging.debug(traceback.format_exc())
	shutdown()
