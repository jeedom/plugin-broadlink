#!/usr/bin/python
from broadlink import broadlink
import logging
import time
import json

def read_lb1(device):
	result ={}
	host = device['ip']
	port = device['port']
	mac = device['mac']
	name = device['name']
	product = broadlink.gendevice(0x60c8,host=(host,int(port)), mac=bytearray.fromhex(mac))
	logging.debug("[PY]Connecting to Broadlink device with name " + name + "....")
	product.auth()
	logging.debug("[PY]Connected to Broadlink device with name " + name + "....")
	data = product.get_state()
	data["mac"]=device['mac']
	return data

def send_lb1(device):
	logging.debug("send_lb1(device)")
	pwr=None
	red=None
	blue=None
	green=None
	brightness=None
	colortemp=None
	hue=None
	transitionduration=None
	saturation=None
	maxworktime=None
	bulb_colormode=None
	bulb_scenes=None
	bulb_scene=None
	bulb_sceneidx=None
	result ={}
	state = True
	host = device['ip']
	port = device['port']
	mac = device['mac']
	name = device['name']
	if 'pwr' in device:
		pwr=device['pwr']
	if 'red' in device:
		red=device['red']
	if 'blue' in device:
		blue=device['blue']
	if 'green' in device:
		green=device['green']
	if 'brightness' in device:
		brightness=device['brightness']
	if 'colortemp' in device:
		colortemp=device['colortemp']
	if 'hue' in device:
		hue=device['hue']
	if 'transitionduration' in device:
		transitionduration=device['transitionduration']
	if 'saturation' in device:
		saturation=device['saturation']
	if 'maxworktime' in device:
		maxworktime=device['maxworktime']
	if 'bulb_colormode' in device:
		bulb_colormode=device['bulb_colormode']
	if 'bulb_scenes' in device:
		bulb_scenes = device['bulb_scenes']
	if 'bulb_scene' in device:
		bulb_scene = device['bulb_scene']
	if 'bulb_sceneidx' in device:
		bulb_sceneidx = device['bulb_sceneidx']
	product = broadlink.gendevice(0x60c8,host=(host,int(port)), mac=bytearray.fromhex(mac))
	logging.debug("[PY]Connecting to Broadlink device with name " + name + "....")
	product.auth()
	logging.debug("[PY]Connected to Broadlink device with name " + name + "....")
	result = product.set_state(
		pwr=pwr,
		red=red,
		blue=blue,
		green=green,
		brightness=brightness,
		colortemp=colortemp,
		hue=hue,
		transitionduration=transitionduration,
		saturation=saturation,
		maxworktime=maxworktime,
		bulb_colormode=bulb_colormode,
		bulb_scenes=bulb_scenes,
		bulb_scene=bulb_scene,
		bulb_sceneidx=bulb_sceneidx
)
	result["mac"]=mac
	return result
