#!/usr/bin/python
from broadlink import broadlink
import logging
import time
from broadlink.exceptions import ReadError, StorageError
import globals

def read_rm4(device):
	result ={}
	host = device['ip']
	port = device['port']
	mac = device['mac']
	name = device['name']
	product = broadlink.gendevice(0x61a2,host=(host,int(port)), mac=bytearray.fromhex(mac))
	logging.debug("Connecting to Broadlink device with name " + name + "....")
	product.auth()
	logging.debug("Connected to Broadlink device with name " + name + "....")
	result['mac']=mac
	result['temperature'] = product.check_temperature()
	result['humidity'] = product.check_humidity()
	logging.debug(str(result))
	return result

def learn_rm4(device):
	if device['mode'] == 'rf':
		result = learn_rm4_rf(device)
		return result
	result ={}
	host = device['ip']
	port = device['port']
	mac = device['mac']
	if mac[-3:] == 'sub':
		logging.debug("This is a child device original mac is " + mac[:-4])
		mac = mac[:-4]
	name = device['name']
	product = broadlink.gendevice(0x61a2,host=(host,int(port)), mac=bytearray.fromhex(mac))
	logging.debug("Connecting to Broadlink device with name " + name + "....")
	product.auth()
	logging.debug("Connected to Broadlink device with name " + name + "....")
	logging.debug("Enter learning")
	product.enter_learning()
	start = time.time()
	data= None
	while time.time() - start < 10:
		time.sleep(1)
		try:
			data = product.check_data()
		except (ReadError, StorageError):
			continue
		else:
			break
	if data == None:
		logging.debug("No button pressed")
		result['hexcode']= 'no'
	else:
		myhex = data.hex()
		logging.debug("Hex code detected " + myhex)
		result['hexcode']= myhex
	result['learnedCmd']=1
	result['mac']=mac
	product.stop_learning()
	logging.debug("Quit learning")
	return result

def learn_rm4_rf(device):
	result ={}
	host = device['ip']
	port = device['port']
	mac = device['mac']
	if mac[-3:] == 'sub':
		logging.debug("This is a child device original mac is " + mac[:-4])
		mac = mac[:-4]
	name = device['name']
	product = broadlink.gendevice(0x61a2,host=(host,int(port)), mac=bytearray.fromhex(mac))
	logging.debug("Connecting to Broadlink device with name " + name + "....")
	product.auth()
	logging.debug("Connected to Broadlink device with name " + name + "....")
	logging.debug("Enter learning")
	product.sweep_frequency()
	logging.debug("Learning RF Frequency, press and hold the button to learn...")
	start = time.time()
	found=False
	while time.time() - start < 10:
		time.sleep(1)
		if product.check_frequency():
			logging.debug("Found RF Frequency")
			found=True
			globals.JEEDOMCOM.send_change_immediate({'foundfrequency' : 1});
			break
	else:
		logging.debug("RF Frequency not found")
		product.cancel_sweep_frequency()
		globals.JEEDOMCOM.send_change_immediate({'foundfrequency' : 0});
		return result
	if found:
		time.sleep(2)
		globals.JEEDOMCOM.send_change_immediate({'step2' : 1});
		product.find_rf_packet()
		start = time.time()
		data= None
		while time.time() - start < 10:
			time.sleep(1)
			try:
				data = product.check_data()
			except (ReadError, StorageError):
				continue
			else:
				break
		if data == None:
			logging.debug("No button pressed")
			result['hexcode']= 'no'
		else:
			myhex = data.hex()
			logging.debug("Hex code detected " + myhex)
			result['hexcode']= myhex
		result['learnedCmd']=1
		result['mac']=mac
		product.stop_learning()
		logging.debug("Quit learning")
	return result

def send_rm4(device):
	result ={}
	host = device['ip']
	port = device['port']
	mac = device['mac']
	if mac[-3:] == 'sub':
		logging.debug("This is a child device original mac is " + mac[:-4])
		mac = mac[:-4]
	name = device['name']
	hex2send = device['hex2send']
	product = broadlink.gendevice(0x61a2,host=(host,int(port)), mac=bytearray.fromhex(mac))
	logging.debug("Connecting to Broadlink device with name " + name + "....")
	product.auth()
	logging.debug("Connected to Broadlink device with name " + name + "....")
	product.send_data(bytearray.fromhex(hex2send))
	logging.debug("Code Sent....")
	return