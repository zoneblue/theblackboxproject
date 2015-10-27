## What.. it.. is.. ##
The blackbox fills a gap between hosted monitoring services and client only solutions like wattplot. Its a php web application that will run on inexpensive ARM development board computers which interfaces to renewable energy (RE) equipment such as inverters and charge controllers. It remembers all the fine grained historial data and its web server delivers pages of status information and graphs to multiple computers and mobile devices on the local network.

![http://www.zoneblue.org/files/blackbox-status-view.png](http://www.zoneblue.org/files/blackbox-status-view.png)
![http://www.zoneblue.org/files/blackbox-test-setup.jpg](http://www.zoneblue.org/files/blackbox-test-setup.jpg)

## Where it came from ##
The black box project came about as a result of midnite forum enthusiasts idea to use tiny computers like rPi to monitor their solar installations. We are numerically obsessed hackers that happen to own RE systems. The name 'blackbox' was a placeholder for the project that stuck.

## Where it's at ##
As of Sept 2013 we have a early prototype in development and the code is now up in svn. A module has been written for the Midnite Classic charge controller, and a mature C binary available that talks modbus to classics. Testing on Beagle Black, rPi and Cubieboard is underway, and we are starting to get our heads around the resource scarcity on these low power boards.

## What's here ##

  1. [News](News.md)
  1. [Code](http://code.google.com/p/theblackboxproject/source/browse/#svn%2Ftrunk) prerelease / proof of concept
  1. [UI demo](http://zoneblue.org/blackbox/) (nb:hosted, not device connected)
  1. [Documentation](Documentation.md)
  1. OS images: much later.

![http://www.zoneblue.org/files/blackbox-graph2.png](http://www.zoneblue.org/files/blackbox-graph2.png)