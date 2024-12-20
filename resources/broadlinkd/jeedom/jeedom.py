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
#

import logging
from threading import Thread
import time
import requests
from collections.abc import Mapping
import os
from queue import Queue
import socketserver
from socketserver import (TCPServer, StreamRequestHandler)


class jeedom_com():
    def __init__(self, apikey='', url='', cycle=0.5, retry=3):
        self._apikey = apikey
        self._url = url
        self._cycle = cycle
        self._retry = retry
        self._changes = {}
        if self._cycle > 0:
            Thread(target=self.__thread_changes_async, daemon=True).start()
        logging.debug('Init request module v%s', requests.__version__)

    def __thread_changes_async(self):
        if self._cycle <= 0:
            return
        logging.info('Start changes async thread')
        while True:
            try:
                time.sleep(self._cycle)
                if len(self._changes) == 0:
                    continue
                changes = self._changes
                self._changes = {}
                self.__post_change(changes)
            except Exception as error:
                logging.error('Critical error on send_changes_async %s', error)

    def add_changes(self, key: str, value):
        if key.find('::') != -1:
            tmp_changes = {}
            changes = value
            for k in reversed(key.split('::')):
                if k not in tmp_changes:
                    tmp_changes[k] = {}
                tmp_changes[k] = changes
                changes = tmp_changes
                tmp_changes = {}
            if self._cycle <= 0:
                self.send_change_immediate(changes)
            else:
                self.merge_dict(self._changes, changes)
        else:
            if self._cycle <= 0:
                self.send_change_immediate({key: value})
            else:
                self._changes[key] = value

    def send_change_immediate(self, change):
        Thread(target=self.__post_change, args=(change,)).start()

    def __post_change(self, change):
        logging.debug('Send to jeedom: %s', change)
        for i in range(self._retry):
            try:
                r = requests.post(self._url + '?apikey=' + self._apikey, json=change, timeout=(0.5, 120), verify=False)
                if r.status_code == requests.codes.ok:
                    return True
                else:
                    logging.warning('Error on send request to jeedom, return code %s', r.status_code)
                    time.sleep(0.5)
            except Exception as error:
                logging.error('Error on send request to jeedom "%s" retry: %i/%i', error, i, self._retry)
        return False

    def set_change(self, changes):
        self._changes = changes

    def get_change(self):
        return self._changes

    def merge_dict(self, d1, d2):
        for k, v2 in d2.items():
            v1 = d1.get(k)  # returns None if v1 has no value for this key
            if (isinstance(v1, Mapping) and isinstance(v2, Mapping)):
                self.merge_dict(v1, v2)
            else:
                d1[k] = v2

    def test(self):
        try:
            response = requests.get(self._url + '?apikey=' + self._apikey, verify=False)
            if response.status_code != requests.codes.ok:
                logging.error('Callback error: %s %s. Please check your network configuration page', response.status_code, response.text)
                return False
        except Exception as ex:
            logging.error('Callback result as a unknown error: %s. Please check your network configuration page', ex)
            return False
        return True


class jeedom_utils():

    @staticmethod
    def convert_log_level(level='error'):
        LEVELS = {
            'debug': logging.DEBUG,
            'info': logging.INFO,
            'notice': logging.WARNING,
            'warning': logging.WARNING,
            'error': logging.ERROR,
            'critical': logging.CRITICAL,
            'none': logging.CRITICAL
            }
        return LEVELS.get(level, logging.CRITICAL)

    @staticmethod
    def set_log_level(level='error'):
        FORMAT = '[%(asctime)s.%(msecs)03d][%(levelname)s] : %(message)s'
        logging.basicConfig(level=jeedom_utils.convert_log_level(level), format=FORMAT, datefmt='%Y-%m-%d %H:%M:%S')

    @staticmethod
    def stripped(str):
        return "".join([i for i in str if i in range(32, 127)])

    @staticmethod
    def ByteToHex(byteStr):
        return ''.join(["%02X " % ord(x) for x in str(byteStr)]).strip()

    @staticmethod
    def dec2hex(dec):
        if dec is None:
            return 0
        return hex(dec)[2:]

    @staticmethod
    def testBit(int_type, offset):
        mask = 1 << offset
        return (int_type & mask)

    @staticmethod
    def clearBit(int_type, offset):
        mask = ~(1 << offset)
        return (int_type & mask)

    @staticmethod
    def split_len(seq, length):
        return [seq[i:i+length] for i in range(0, len(seq), length)]

    @staticmethod
    def write_pid(path):
        pid = str(os.getpid())
        logging.debug("Writing PID %s to %s", pid, path)
        open(path, 'w').write("%s\n" % pid)


JEEDOM_SOCKET_MESSAGE = Queue()


class jeedom_socket_handler(StreamRequestHandler):
    def handle(self):
        global JEEDOM_SOCKET_MESSAGE
        logging.debug("Client connected to [%s:%d]" % self.client_address)
        lg = self.rfile.readline()
        JEEDOM_SOCKET_MESSAGE.put(lg)
        logging.debug("Message read from socket: %s", lg.strip())
        self.netAdapterClientConnected = False
        logging.debug("Client disconnected from [%s:%d]" % self.client_address)


class jeedom_socket():

    def __init__(self, address='localhost', port=55000):
        self.address = address
        self.port = port
        socketserver.TCPServer.allow_reuse_address = True

    def open(self):
        self.netAdapter = TCPServer((self.address, self.port), jeedom_socket_handler)
        if self.netAdapter:
            logging.debug("Socket interface started")
            Thread(target=self.loopNetServer).start()
        else:
            logging.debug("Cannot start socket interface")

    def loopNetServer(self):
        logging.debug("LoopNetServer Thread started")
        logging.debug("Listening on: [%s:%d]" % (self.address, self.port))
        self.netAdapter.serve_forever()
        logging.debug("LoopNetServer Thread stopped")

    def close(self):
        self.netAdapter.shutdown()

    def getMessage(self):
        return self.message
