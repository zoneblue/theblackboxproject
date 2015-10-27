[Home](http://code.google.com/p/theblackboxproject/)
[Contents](Documentation.md)

## 1. Summary ##

  * Each unique brand/model of device requires its own module, better support for multiple same devices coming
  * So far the only module is for midnite classic.
  * All the code for each module resides in its own /modules/{module-name} folder.
  * Each module must have a config file, a module class file. Other files are optional.
  * The module label is used for the folder name, the class name, the class file name and the config file name.
  * You may disable a module with a preceding underscore in the folder name.

These notes are not comprehensive, pending possible major architecture revisions.

## 2. Datapoint types ##

There are two types of dp, that relates the source of the dp's data.

#### sampled ####

Sampled dps are a small important handful of raw data read from actual devices However it need not be a device per se, but could also be an api lookup for example for weather data, or a purely mathmatical model. read device is triggered at regular intervals by a cron job.  An example dp would be battery voltage.

#### derived ####

These are not read from the device but derived from either another sampled dp or several dps, or perhaps an agregation of dps over the period of a day. An example of the former is english charge state, and an example of the latter is minimum battery voltage.

## 3. Module class ##

The module's class is extended from the Module abstract class. The abstract defines all the default functionality and it defines the two methods that must be defined for all modules.

### Method: define\_datapoints() ###

This method defines the datapoint defintions for the module. The array key is the datapoint label. The format for the vout definition is like this example:

```
$defns['vout']= array(
   'name'=>       'Output Voltage',
   'type'=>       'sampled',      //sampled|derived
   'store'=>      true,           //(in db) true|false 
   'interval'=>   'periodic',     // periodic,day minute|week|month|year
   'method'=>     'get_register', //method to use to get dp
   'argument'=>   '[4115]/10',    //method arg
   'comment'=>    'Output voltage at controller, near equal to battery voltage, 1dp',
   'unit'=>       'V',
   'priority'=>   1,              //dictates use by default view
   'order'=>      $order++,       //order in setup listings
);
```

### Method: read\_device() ###

This method is reads a single sample of all dps from the module's device. It will typically lean on additional scripts or binarys to speak the appropriate comms protocol. Any required post processing should be included in the module.
How you read the device is up to you, but most likely the most efficient way is to read all dps at once. You may need to post process the raw data, for instance map the raw register values to english, or change the units from seconds to hours, tenths of a volt to volts etc. The method returns an associative array with the dp label as key and the dp value as value.

### Optional methods ###

You may of course use any other helper methods as required. For derived dps, you must specify and provide a method for the dp, along with its optional argument, will be called to derive the value. The derivitive methods give a result for a single dp, however it may return an array for the whole periodic series, or a single value for daily agregations. For something like english charge state there is no need to store the result, its more for current status view, but for something like min battery voltage, you would store the result as a day interval.