[Home](http://code.google.com/p/theblackboxproject/)
[Contents](Documentation.md)


## 1.0 Introduction ##

### 1.1 Objective ###
To collaborate in the development of open source software to monitor renewable energy systems, that can operate on tiny computer hardware.

### 1.2 Purpose of the Black box ###
  * Connect to and extract data from renewable energy (RE) hardware, (possibly also weather and home automation gear)
  * Store, process and present data to various client communication devices
  * Communicate over the internet with other servers, clients and other black boxes.

### 1.3 Problem / Opportunity ###
Operators of RE systems need ready access to real time and historic data for their systems in order to best manage them. Better quality RE equipment, such as inverters and charge controllers all include data collection and communications protocols of some sort. However these protocols vary from product to product in an often incompatible manner. Also the devices usually have insufficient computing resources to undertake the required level of processing and storage. The opportunity here is to create a black box computer that can talk to multiple RE devices in a open modular manner, that can deliver that information in  diverse user tailorable ways. Blackbox is a local solution that complements directly hosted solutions such as mymidnite.

### 1.4 Description ###
While the software will be installable on a range of computers, it is targeted at the recent line of small development boards.  These low cost boards are capable of operating as a web server running continuously with very low power consumption.

The primary requirement is to serve web pages to client devices within the installation’s local area network.  For instance a 7” tablet could be mounted on the kitchen wall, which shows power use metrics and battery SOC. However with the help of internet hosted ‘node’ servers, client devices could access blackbox derived data over the internet, and similarly, to allow blackboxes to network with other blackboxes for instance where installations span multiple sites.

The blackbox interacts with the RE devices using whatever methods are required, eg serial, usb, or wired or wireless ethernet.  In this way large numbers of devices can be connected. The software is to be open source, so as to maintain a strong focus on end users needs, and bridge the gulf between various manufacturers protocols. While starting with the midnite classic, with a module system, support for individual products can be added incrementally as the user base expands.

### 1.5 Project Outcomes ###
The project is envisaged as encompassing the following components:
  * SVN repository for the main black box software, and modules
  * Wiki for software documentation, issues tracker etc
  * Howtos , Recommended hardware lists, OS images, etc

## 2.0 Design ##

As pretty much everything is still up for discussion, the below is a place to start the discussion.

### 2.1 Technologys ###
| **Layer** | **Recommended** | **Reason** |
|:----------|:----------------|:-----------|
| Target device | rPi, beaglebone, mele, cubieboard, odroid etc | Sub 2W power consumption while running linux capably. |
| OS        | Linux, debian stable, ubunu server or similar | Open source, stable, common, lite. |
| Web server | Lighttp/nginx   | Ditto      |
| Database  | Sqlite/mysql    | Ditto      |
| Web app preprocessor | PHP             | Ditto      |
| Scripting | python/bash/etc | As required |
| Low level | c/c++/etc       | As required |
| Primary networking | Ethernet, http  | Most flexible, and common |
| Other coms | RS232           | Connect certain RE devices |

+ Asp, dotnet and java are not envisaged (not meeting lite or open criteria)

### 2.2 Research ###
By way of reference see the following related projects and resources.
See also http://www.google.co.nz/search?q=renewable+energy+monitoring

#### Open energy monitor ####
Closely related project targeted at grid tie primarily. On an open source basis they have designed and produced RF linked data collection node devices. Worth closer inspection.
  * http://openenergymonitor.org/emon/applications/solarpv

#### Green monitor ####
Greenmonitor is a neatly packaged commercial solution. Based on geode boards and java. They opened their java modbus stack, but not much else to see or learn.
  * http://www.ghgande.com/greenMonitor.html

#### Mango ####
Comprehensive open source industrial telemetry software. Well regarded and powerful, but written in java. Very unlkely to run on arm devices, Will be a great source of ideas.
  * http://mango.serotoninsoftware.com/

#### Nagios ####
Ditto, except not java. Further investigation needed.
  * http://www.nagios.org/

#### Wattplot ####
As a commercial windows client based offering this has a reasonable following, and is mature. Works on outback only, not sure what we can learn from them, seems like a pretty closed shop.
  * http://www.wattplot.com/

#### N1GNN ####
Similar to Wattplot, tailor made targetted at Outback AXS, and Midnite Classic. Uses open, read and close, to allow other apps access, and can also config the classic in place of the local app. Windows only, freeware, closed source. No logging.
  * http://www.n1gnn.com/StatusMonitor/StatusMonitor.html

#### Fpd/solarspy ####
This project is the closest thing to what blackbox is that weve been able to find. It hails from the grid tie world and hence they have chosen a two server solution. The smaller server runs code (in c) that talks to the devices and pushes the data to a second internet hosted server. Having more resources, that server handles the UI, bulk storage, and solves  the access anywhere problem. Plenty of scope for cooperation with these guys.
  * https://www.solarspy.net/info
  * https://github.com/alxnik/fpd

#### Hosted services ####
  * http://www.mymidnite.com/
  * http://www.xively.com/
  * http://pvoutput.org/
  * http://www.splashmonitoring.com/ (streambox)
  * http://www.sma-america.com/en_US/products/monitoring-systems/sunny-portal.html

#### Newmodbus ####
Tiny c binary that talks modbus to midnite classic and possibly other modbus over ethernet devices.  Its faster than anything else out there by a long shot. Ideal as a backbone to the module system.
  * http://support.rossw.net/midnite/
  * http://midniteforum.com/index.php?topic=1335.0 (docs)

#### Interface devices ####
For direct current/voltage/temp measurement, additional adc type interface boards are envasaged support for. Low cost examples with good linux support:
  * http://labjack.com/u3
  * http://www.cainetworks.com/products/webcontrol

#### Stray, vaguely connected ####
  * https://code.google.com/p/wfrog/
  * http://www.schneider-electric.com/products/ww/en/7000-solar-off-grid-and-back-up/7030-monitoring/62089-conext-combox/?BUSINESS=7
  * http://sourceforge.net/p/mate-data-tool/wiki/Home/
  * http://outbackpower.com/forum/viewtopic.php?f=25&t=2882&start=0
  * http://www.raspberrypi.org/phpBB3/viewtopic.php?f=37&t=11955
  * http://www.mitos-solutions.gr/en/services/operation-and-maintenance/solarspy/