[Home](http://code.google.com/p/theblackboxproject/)
[Contents](Documentation.md)

### Update: 2014-08-09 ###

Some minor datapoints added, (min/max soc, rest voltage, uptime). Sign of WbJr current reversed to align with midnites convention. Newmodbusd functionality reorgnised a bit. Still a work in progress, doenst start on boot etc. BTW Classic firmware 1849 came out in April with a raft of promised network fixes.

### Update: 2014-03-16 ###

Latest classic firmware has much improved support for ending amps, and state of charge. However the classic has started some sporadic self reboots, as a consequence of all the new firmware code. Hence in this commit newmodbusd has been  modified to better tolerate the classic resets (for whatever reason). SOC datapoint added to module.

Hopefully this is the last of the stability issues resolved, and we can get on with doing more work on the UI, which is currently rudimentaty. Also i plan to add some filtering to the newmodbusd 1sec data to remove some of the noise on Icc and Iwbjr.

### Update: 2014-01-12 ###

Highest stable sample interval of 3 minutes, led to the develepment of a daemon version of newmodbus. Source code for both command line and daemon now uploaded with Ross's permission. Newmodbusd now finally allows us to sample the classic over ethernet at rates as fast as 100ms. Given that the main registers are one second averages, a one second sample rate is basically perfect. Rudimentary support for daemon mode added in [r33](https://code.google.com/p/theblackboxproject/source/detail?r=33).

### Update: 2013-10-29 ###

Midnite have now released their battery current monitor addon (Whizbang Jr) for the classic, and it appears to work very well. The latest revision has rudimentry support for it. Battery current,load current and power,load Wh/d, Ah/d in and out. Note that the modbus connection problem persists, and the best solution for it is to reduce the sample interval to 5 minutes. This suggest to me that the classic isnt closing connections properly, and consequently refusing to open new ones. The other solution is to keep a single connection open, and its likely that support for this will be added shortly.

### Update: 2013-09-30 ###

The current revision [r24](https://code.google.com/p/theblackboxproject/source/detail?r=24) is relatively stable, having been running for 3 weeks on the prototype. It contains fixes to allow for sample times greater than 60 seconds. This arose because it was discovered that a 2 min sample time largely works around the Classics modbus problems.  We are hopefully near to resolving the issue with failed conects to the Classic, as midnite have reported discovered a curly brace where it didnt belong. A new firmware is due to fix this, and also contains code for the new battery monitor. And days between bulk. What we would call christmas, all told.

### New project: 2013-09-16 ###

Project site is now up, an initial commit made of proof of concept code line, and some start on the documentation. A bug in the classic has been discovered that impacts of connections over modbus/tcp. The problem occurs only on lower speed cpus like rPi, and appears as either occasional refused connects, or time outs. It has been shown not to occur on intel atom, or via chips, but confirmed on both rPi and cubie1. Dialog entered into with midnite.