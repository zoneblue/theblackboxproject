#!/usr/bin/python

#USAGE
#chmod 0755 readclassic.py
#./readclassic.py 192.168.0.223

from pymodbus.client.sync import ModbusTcpClient
import sys


HOST= str(sys.argv[1])
client = ModbusTcpClient(HOST)

def connect():
        global client
        connected = 0
        count = 0
        while (not connected):
                resp = client.connect()
                if (resp == False):
                	sys.exit(1);
                	#time.sleep(10)
                else:
                        connected = 1
                count = count + 1


def close():
        global client
        client.close()

#main, pymodbus cant read more then 123 addresses at a time
rq = client.read_holding_registers(4100,120)
for x in range(0,120):
        reg= 4100 + x + 1 
        val = rq.registers[x]
        print(str(reg)+" "+str(val))

rq = client.read_holding_registers(4220,120)
for x in range(0,120):
        reg= 4220 + x + 1
        val = rq.registers[x]
        print(str(reg)+" "+str(val))

rq = client.read_holding_registers(16384,6)
for x in range(0,6):
        reg= 16385 + x + 1
        val = rq.registers[x]
        print(str(reg)+" "+str(val))

sys.exit(0);