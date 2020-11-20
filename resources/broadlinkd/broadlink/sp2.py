#!/usr/bin/python
from broadlink import broadlink
import logging
import time

def read_sp2(device):
	result ={}
	host = device['ip']
	port = device['port']
	mac = device['mac']
	name = device['name']
	product = broadlink.gendevice(0x2711,host=(host,int(port)), mac=bytearray.fromhex(mac))
	logging.debug("Connecting to Broadlink device with name " + name + "....")
	product.auth()
	logging.debug("Connected to Broadlink device with name " + name + "....")
	result['mac']=mac
	data = product.check_power()
	if data == True:
		result['s1']=1
	else:
		result['s1']=0
	logging.debug(str(result))
	data_poweroutput = product.get_energy()
        if data_poweroutput != None and result['s1']==1:
            result['power_output']=data_poweroutput
        else:
            result['power_output']=0
	logging.debug("value from sp2="+str(result))
	return result

def send_sp2(device):
	result ={}
	state = True
	host = device['ip']
	port = device['port']
	mac = device['mac']
	name = device['name']
	wantedstate = device['state']
	if int(wantedstate) == 0:
		state = False
	product = broadlink.gendevice(0x2711,host=(host,int(port)), mac=bytearray.fromhex(mac))
	logging.debug("Connecting to Broadlink device with name " + name + "....")
	product.auth()
	logging.debug("Connected to Broadlink device with name " + name + "....")
	product.set_power(state)
	time.sleep(0.1)
	result = read_sp2(device)
	return result
