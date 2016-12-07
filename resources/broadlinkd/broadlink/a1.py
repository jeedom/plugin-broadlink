#!/usr/bin/python
import broadlink
import logging
import time

def read_a1(device):
	result ={}
	host = device['ip']
	port = device['port']
	mac = device['mac']
	name = device['name']
	product = broadlink.a1(host=(host,int(port)), mac=bytearray.fromhex(mac))
	logging.debug("Connecting to Broadlink device with name " + name + "....")
	product.auth()
	logging.debug("Connected to Broadlink device with name " + name + "....")
	result['mac']=mac
	raw = product.check_sensors_raw()
	human = product.check_sensors()
	result['temperature'] = raw['temperature']
	result['humidity'] = raw['humidity']
	result['luminosity'] = raw['light']
	result['air'] = raw['air_quality']
	result['noise'] = raw['noise']
	result['luminosity_human'] = human['light']
	result['air_human'] = human['air_quality']
	result['noise_human'] = human['noise']
	logging.debug(str(result))
	return result
