[Home](http://code.google.com/p/theblackboxproject/)
[Contents](Documentation.md)

## Intro ##

The Midnite Classic has several communications ports, the strengths of each will be noted here. Documentation for the ports are minimal, see the classic manual, the modbus specification, and the forum archives. All ports are located internally, requiring front cover removal.

### 1. Ethernet Port ###

The RJ45 jack in located in the left side of the wiring bay. One quirk of the port is that the release tab is downside to the control board and is tricky to release. The port is limited to two active tcp/ip connections, nominally one for mymidnite and one for the local app.

#### MyMidnite ####

MyMidnite, appears to work on a push basis, when enabled it sends http packets to the mymidnite server containing basic status information every 15 minutes. This gets around home router port forwarding issues.

The packets are DES encrypted for security. User control over this system is limited to enabling or disabling the service. While you may be able to redirect this traffic using a hosts file, Midnite have stated that they do not intend to provide encryption keys or handshaking documentation that would otherwise allow users to make use of this for local monitoring.

While the interval could conceivably be configurable, the present fixed 15 minute timing interval is an downside for local monitoring, and this is understood to be driven by the volume of customer data server side.

The MyMidnite service is useful, and they plan to create a developer API that will allow third party access to the data. However the service understandably limits the storage of daily level data to a period of three weeks.

#### Modbus over TCP/IP ####

The remaining ethernet connection uses modbus, port 502 default. There is no http access, and while Midnite had hoped to add a small embedded webserver at some point that now looks less likely. Hence all monitoring solutions must rely on this single modbus over TCP/IP link.

There are two kinds of connection, open read and close; or persistant. While the former is attractive becasue it allows other processes to share access, the firmware has a long standing bug that materially affects this mode of operation. The bug/incompatility shows up eratically, particularly with linux networking, particular on smaller devices. Their developers have acknowledged its existance put deprioritised its resolution.

Its symptoms are that opening and closing connections in too quick a sucession will cause some connection requests to timeout, and then if persisting sometime later the modbus port will refuse connections completely, requiring a controller reboot to restore connectivity. Perhaps the connections are being closed out properly?

The easiest way to reproduce this bug with blackbox is to set it to use new connections at a 1 minute interval. This will drop a few percent of requests per day for about 2 days then lock up. Using a three minute interval it is more or less stable. Local app, and mobile app users from time to time stumble across this issue as well, and i think ultimately the new mobile software will serve as a driver for its resolution.

However this issue effectively prevents blackbox using open and close connections if a sample rate less then 3 minutes is required.

Using persistant connections appears not to exibit this timeout issue at all, and has the additional merit of very fast sample rates. Rates in the order of 50-100ms have been shown practical. As a modbus packet is 256 bytes you can in practice effectively request up to 123 16 bit registers per call. To get the entire 350 odd registers requires 4 calls. This can be achieved even on a slow ARM computer within 15ms round trip.

But remember that using persistant connections does have the effect of locking out the local app, the android apps etc. By monopolising the single modbus link, your app will need to effectively provide a 100% comprehensive service to all downstream users via http, or conceivably by providing a modbus master service/modbus repeater.

### 2. USB Port ###

The USB port (mini b) is located just next to the ethernet port. Unlike the ethernet port it has a core functional requirement, that of updating the controller's firmware. Using the USB port for monitoring would require cable juggling.

This works as plain serial over USB, 9600 baud, 8N1.
The USB port can be enabled in one of several modes. Currently the interesting modes are mode 0, which prints several samples per second, one sample per line, 6 registers. eg:

```
68.9,   68.8,   25.0,    1.9,    0.6,    46
68.9,   68.8,   25.0,    1.9,    0.6,    47
68.9,   68.8,   25.0,    1.8,    0.6,    46
68.9,   68.8,   25.0,    1.8,    0.6,    46
68.9,   68.8,   25.0,    1.8,    0.5,    46
68.9,   68.8,   25.0,    1.8,    0.5,    46
...
```

Mode 1 prints the entire register range one per line on a rolling basis, over about a 30s period Eg:

```
4373,     96
4374,    317
4375,      0
4376,     14
4377,     355
4378,  10143
...
```

There is also an interactive mode, mode 3, which is currently undocumented.

Other than the firmware use contention, the primary constraint appears to be that it is not possible to get a significant number of registers at quick enough sample rates. If say a 1 minute interval was acceptable, mode 1 would work, or if midnite were to update mode0 to include WBjr/SOC info, you could possible alternate between modes as needed, eg getting daily data once a day. EPROM wear issues excepting.

### 3. RS232 Ports ###

The classic has three rs232 ports on the upper half of the control board. The intended use of these RJ11 telephone style ports is for the MNGP/MNLP and master/slave followme controller interconection. However people have used them for monitoring. The ports operate at RS232 levels (not TTL) 19200 baud, and the pinout is documented on the last page of the classic manual.

Running Modbus RTU, and default to modbus ID address 10 (decimal).  While somwhat slower than tcp, you ought to be able to get the entire register range in something like 800ms. One very appealing attraction to using serial is that it requires significantly less power consumption to run a serial port compared to an ethernet port. Ethernet typically draws 0.6W for each end of the cable, plus processing overhead. Newer energy saving devices reduce this by some, by sleeping inactive links, but an example of a modern 5 port switch will draw 1.5W for three active connections. Add to that whatever the classic uses to maintain its end 24/7 and the total power cost for using blackbox with ethernet could be into the 3W range, when compared to almost nothing for serial.

While these ports have real potential, I think the followme constraint may be a deal breaker. Maybe theres a way to share these ports? Blackbox needs to be useable for typical system configurations and followme is often enough encountered.