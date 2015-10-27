[Home](http://code.google.com/p/theblackboxproject/)
[Contents](Documentation.md)

## Structure ##

The bulk of the model logic resides in the file /lib/lib-blackbox.php. This file contains a heirarchy of three classes that comprise the blackbox module system. The three classes are Blackbox, Module and Datapoint, each the child of the previous.

```
$blackbox->modules['midnite_classic']->datapoints['vbat']->current_value;
```

In general there are two ways to use modules. Each minute, when called from cron, the devices are read, and the current data stored. Also browser clients will request view screens for present day or historic data, which is retrieved from the database. At the moment, graphs are gd rendered as part of the device read process, but its likely graphing will in future be farmed out to browser clients using javascript vector graphing.

Each module has two db tables, one for the periodic dataset, one for the day dataset. The former gets a new record added each minute, the latter gets the current days record overwritten all day, and the last one standing wins. If the device read fails an empty record is added with a field called code set to a positive integar to signify an error.

My thoughts are that while modules should have their own db table and sample interval, that the blackbox level should impose on all modules named time series, ie. each minute for a day, each day for a year etc. Data is stored in plain integer indexed arrays at the datapoint level. The timestamp keys for this data is currently held at the module level, and periodic equals 1 minute intervals. When theres a minute series, periodic will remain as 'whatever interval the module was actually sampled at'.

The process of sampling each device is a bit involved because of the processing that must be carried out to map values to more usable form and to agregate periodic data into day data.

#### 1. Blackbox ####

This is a minimal container which houses the module instances. At present all it does is scan the modules folder for modules, and invoke them, with the instances stored in an array property called modules, keyed by the module label.

There is a method to automatically check and populate db tables and columns, which is used by the setup UI.

My feeling is that much of the module abstract could be moved here, to reduce the memory footprint when there are multiple modules. But that will depend on decisions pending re the constraints placed on modules.

#### 2. Module ####

The Module class is an abstract that the modules extend from. Most of the blackbox code is found here. Individual module classes are named by their module name and located in /modules/{module name}/{module name}.php. Methods that must be defined by individual modules are: define\_datapoints and read\_device. Public methods defined in the abstract are as follows:

#### constructor ####

Looks for and parses the module config file, initialises the datetime series, and the loads the child datapoint instances into an array proerty $module->datapoints.

#### process\_device ####

Usage: $module->process\_device()

This is called from a cronjob to regularly sample the device and store the data. It calls: read\_device, read\_dbase, calc\_derived, then write\_dbase in sequence. It does this so that daily agregations can be calculated and stored.

Nb: At present its assumed that periodic=minute, thus cronjob is one minute intervals.
Later more frequent periodics may be catered for, and a minute agregation added.

#### load\_data ####

Usage: $module->load\_data() or $module->load\_data('2013-08-01')

This is used by UI applications to instruct the module to read the database, and populate its datapoints' data, incl derived data.
Works on a single calendar day at a time. Ie: get the current day to date is its default beahvior. Plus the year to date of daily agregate data. If you need more than that you can call it multiple times. Calling the parent module constructor with a true flag will force load\_data().

#### get\_datetimes ####

Usage: $arr= $module->get\_datetimes('periodic')

Returns an array of the various time series, with the same integer keys as the datapoint data. The timestamps are iso datetime format for current\_value and periodic, iso date format for day or greater.

#### derivitives ####

The abstract provides some std derivitive functions: min, max, mean agregations.

#### 3. Datapoint ####

This class minimally defines a datapoint, ie it houses the definition, and its data values.
At present its naive as to its parents, and does little. A basic contingent of getters and setters.

## Controlling logic ##

  * /index.php renders views.
  * /setup.php builds views, and manages the database.
  * /config/main-config.php , db config plus any global setup
  * several support librarys in /lib: form,db,page,draw,graph.
  * basic template engine from lib/lib-page.php,  renders webpages from templates found in /templates

The template engine is simple enough, relatively light on resources. Template tags use curley braces eg {Body}, are case sensitive, a-z characters only. An optional double colon may be used as a seperator.

{Pane::name} type tags have special meaning in the show view UI, its where dps are shown. There is no limit to the number of pane tags.
The default view provides a top, bottom, left, and right panes, and three classes of elements.
The file /setup.php allows users to program views to show certain elements, in certain styles. Its a fairly bland default style but open ended enough to allow more creative presentations.

## Final thoughts ##

As the ARM boards have minimal processing resources it is important that code development considers cpu time in particular. DB and floating point math are the two main choke points. We plan to replace the database with a flat file system so that each days periodic/minute series is in its own file. The more modules there are, the more client browsers there are, creates limits at which embedded computers are able to perform. We have two choices: contniue to optimise, or break the system into two peices, and pass off UI rendering to other (hosted) devices.