#!/usr/bin/python
import broadlink
import logging
import time

def read_rm2(device):
	result ={}
	host = device['ip']
	port = device['port']
	mac = device['mac']
	name = device['name']
	product = broadlink.rm(host=(host,int(port)), mac=bytearray.fromhex(mac))
	logging.debug("Connecting to Broadlink device with name " + name + "....")
	product.auth()
	logging.debug("Connected to Broadlink device with name " + name + "....")
	result['mac']=mac
	result['temperature'] = product.check_temperature()
	logging.debug(str(result))
	return result

def learn_rm2(device):
	result ={}
	host = device['ip']
	port = device['port']
	mac = device['mac']
	if mac[-3:] == 'sub':
		logging.debug("This is a child device original mac is " + mac[:-4])
		mac = mac[:-4]
	name = device['name']
	product = broadlink.rm(host=(host,int(port)), mac=bytearray.fromhex(mac))
	logging.debug("Connecting to Broadlink device with name " + name + "....")
	product.auth()
	logging.debug("Connected to Broadlink device with name " + name + "....")
	logging.debug("Enter learning")
	product.enter_learning()
	time.sleep(5)
	ir_packet = product.check_data()
	myhex = str(ir_packet).encode('hex')
	if ir_packet == None:
		logging.debug("No button pressed")
		result['hexcode']= 'no'
	else:
		logging.debug("Hex code detected " + myhex)
		result['hexcode']= myhex
	result['learnedCmd']=1
	result['mac']=mac
	product.stop_learning()
	logging.debug("Quit learning")
	return result

def send_rm2(device):
	result ={}
	host = device['ip']
	port = device['port']
	mac = device['mac']
	if mac[-3:] == 'sub':
		logging.debug("This is a child device original mac is " + mac[:-4])
		mac = mac[:-4]
	name = device['name']
	hex2send = device['hex2send']
	product = broadlink.rm(host=(host,int(port)), mac=bytearray.fromhex(mac))
	logging.debug("Connecting to Broadlink device with name " + name + "....")
	product.auth()
	logging.debug("Connected to Broadlink device with name " + name + "....")
	product.send_data(hex2send.decode('hex'))
	logging.debug("Code Sent....")
	return