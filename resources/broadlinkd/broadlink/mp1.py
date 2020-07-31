#!/usr/bin/python
from broadlink import broadlink
import logging
import time

def read_mp1(device):
	result ={}
	host = device['ip']
	port = device['port']
	mac = device['mac']
	name = device['name']
	product = broadlink.gendevice(0x4eb5,host=(host,int(port)), mac=bytearray.fromhex(mac))
	logging.debug("Connecting to Broadlink device with name " + name + "....")
	product.auth()
	logging.debug("Connected to Broadlink device with name " + name + "....")
	result['mac']=mac
	data = product.check_power()
	for x in data:
		if data[x]:
			result[x]=1
		else:
			result[x]=0
	logging.debug(str(result))
	return result

def send_mp1(device):
	result ={}
	state = True
	host = device['ip']
	port = device['port']
	mac = device['mac']
	name = device['name']
	sid = device['sid']
	wantedstate = device['state']
	if int(wantedstate) == 0:
		state = False
	product = broadlink.gendevice(0x4eb5,host=(host,int(port)), mac=bytearray.fromhex(mac))
	logging.debug("Connecting to Broadlink device with name " + name + "....")
	product.auth()
	logging.debug("Connected to Broadlink device with name " + name + "....")
	if sid == 'all':
		for i in range(1,5):
			product.set_power(i,state)
	else:
		product.set_power(int(sid),state)
	time.sleep(0.1)
	result = read_mp1(device)
	return result