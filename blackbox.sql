


######################################################################
# VIEW TABLES
######################################################################


#BLACKBOXVIEWS
#User defined view definitions

create table blackboxviews (
	id_view      int unsigned primary key auto_increment,
	viewname     varchar(255) not null,
	type         char(1) not null,
	settings     text not null,
	position     tinyint unsigned not null
);


#BLACKBOXELEMENTS
#User defined view element definitions
#type is (g)raph or (d)atapoint
#settings holds serialised array of settings 

create table blackboxelements (
	id_element   int unsigned primary key auto_increment,
	id_view      tinyint unsigned not null,
	name         varchar(255) not null,
	type         char(1) not null,
	panetag      varchar(255) not null,
	settings     text not null,
	position     tinyint unsigned not null
);

INSERT INTO blackboxviews SET `id_view`='1',`position`='1';

# these arent necessary but will get a the default view running until the default self populates

INSERT INTO blackboxelements SET `id_element`='1',`id_view`='1',`name`='Pout',`type`='d',`panetag`='Top',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:4:"pout";s:10:"resolution";i:0;s:5:"style";s:5:"large";}',`position`='1';
INSERT INTO blackboxelements SET `id_element`='2',`id_view`='1',`name`='Stage',`type`='d',`panetag`='Top',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:9:"stageword";s:10:"resolution";s:0:"";s:5:"style";s:5:"large";}',`position`='2';
INSERT INTO blackboxelements SET `id_element`='3',`id_view`='1',`name`='Vbat',`type`='d',`panetag`='Top',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:4:"vout";s:10:"resolution";s:1:"1";s:5:"style";s:5:"large";}',`position`='3';
INSERT INTO blackboxelements SET `id_element`='4',`id_view`='1',`name`='Solar',`type`='g',`panetag`='Right',`settings`='a:8:{s:5:"width";i:680;s:6:"height";i:230;s:4:"ymax";i:1800;s:7:"average";i:1;s:9:"linethick";i:2;s:10:"linesmooth";i:1;s:7:"datapts";a:2:{i:1;a:5:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:4:"pout";s:9:"linecolor";s:4:"blue";s:4:"name";s:13:"Power Out (W)";s:10:"multiplier";d:1;}i:2;a:5:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:8:"stagelin";s:9:"linecolor";s:3:"red";s:4:"name";s:12:"Charge Stage";s:10:"multiplier";d:200;}}s:4:"ymin";i:0;}',`position`='1';
INSERT INTO blackboxelements SET `id_element`='7',`id_view`='1',`name`='Iout',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:4:"iout";s:10:"resolution";s:1:"1";s:5:"style";s:6:"medium";}',`position`='2';
INSERT INTO blackboxelements SET `id_element`='8',`id_view`='1',`name`='Imax',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:7:"maxiout";s:10:"resolution";s:1:"1";s:5:"style";s:6:"medium";}',`position`='3';
INSERT INTO blackboxelements SET `id_element`='9',`id_view`='1',`name`='Vmax',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:7:"maxvbat";s:10:"resolution";i:1;s:5:"style";s:6:"medium";}',`position`='5';
INSERT INTO blackboxelements SET `id_element`='10',`id_view`='1',`name`='Absorb',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:9:"durabsorb";s:10:"resolution";i:1;s:5:"style";s:6:"medium";}',`position`='5';
INSERT INTO blackboxelements SET `id_element`='11',`id_view`='1',`name`='Float',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:8:"durfloat";s:10:"resolution";i:1;s:5:"style";s:6:"medium";}',`position`='6';
INSERT INTO blackboxelements SET `id_element`='12',`id_view`='1',`name`='In',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:7:"whtotal";s:10:"resolution";s:1:"0";s:5:"style";s:6:"medium";}',`position`='1';
INSERT INTO blackboxelements SET `id_element`='13',`id_view`='1',`name`='Vpv',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:3:"vpv";s:10:"resolution";i:1;s:5:"style";s:6:"medium";}',`position`='4';
INSERT INTO blackboxelements SET `id_element`='14',`id_view`='1',`name`='Eff',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:3:"eff";s:10:"resolution";i:0;s:5:"style";s:6:"medium";}',`position`='4';
INSERT INTO blackboxelements SET `id_element`='15',`id_view`='1',`name`='Battery',`type`='g',`panetag`='Right',`settings`='a:7:{s:5:"width";i:680;s:6:"height";i:220;s:4:"ymax";i:35;s:7:"average";i:1;s:9:"linethick";i:2;s:10:"linesmooth";i:1;s:7:"datapts";a:2:{i:1;a:5:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:4:"vout";s:9:"linecolor";s:4:"teal";s:4:"name";s:18:"Battey Voltage (V)";s:10:"multiplier";d:1;}i:2;a:5:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:4:"tbat";s:9:"linecolor";s:6:"orange";s:4:"name";s:16:"Battery Temp (C)";s:10:"multiplier";d:1;}}}',`position`='2';
INSERT INTO blackboxelements SET `id_element`='16',`id_view`='1',`name`='Vmin',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:7:"minvbat";s:10:"resolution";i:1;s:5:"style";s:6:"medium";}',`position`='4';
INSERT INTO blackboxelements SET `id_element`='20',`id_view`='1',`name`='Time',`type`='d',`panetag`='Top',`settings`='a:4:{s:6:"module";s:16:"insolation_model";s:9:"datapoint";s:4:"time";s:10:"resolution";i:1;s:5:"style";s:5:"large";}',`position`='4';
INSERT INTO blackboxelements SET `id_element`='21',`id_view`='1',`name`='Az',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:16:"insolation_model";s:9:"datapoint";s:7:"azimuth";s:10:"resolution";i:0;s:5:"style";s:6:"medium";}',`position`='10';
INSERT INTO blackboxelements SET `id_element`='22',`id_view`='1',`name`='Zen',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:16:"insolation_model";s:9:"datapoint";s:6:"zenith";s:10:"resolution";i:0;s:5:"style";s:6:"medium";}',`position`='11';
INSERT INTO blackboxelements SET `id_element`='23',`id_view`='1',`name`='Am',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:16:"insolation_model";s:9:"datapoint";s:2:"am";s:10:"resolution";i:1;s:5:"style";s:6:"medium";}',`position`='12';
INSERT INTO blackboxelements SET `id_element`='24',`id_view`='1',`name`='Aoi',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:16:"insolation_model";s:9:"datapoint";s:3:"aoi";s:10:"resolution";s:1:"0";s:5:"style";s:6:"medium";}',`position`='13';
INSERT INTO blackboxelements SET `id_element`='25',`id_view`='1',`name`='Insol',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:16:"insolation_model";s:9:"datapoint";s:6:"power1";s:10:"resolution";s:1:"0";s:5:"style";s:6:"medium";}',`position`='14';
INSERT INTO blackboxelements SET `id_element`='26',`id_view`='1',`name`='Amf',`type`='d',`panetag`='Bottom',`settings`='a:4:{s:6:"module";s:16:"insolation_model";s:9:"datapoint";s:3:"amf";s:10:"resolution";i:2;s:5:"style";s:5:"small";}',`position`='14';
INSERT INTO blackboxelements SET `id_element`='27',`id_view`='1',`name`='Aoif',`type`='d',`panetag`='Bottom',`settings`='a:4:{s:6:"module";s:16:"insolation_model";s:9:"datapoint";s:4:"aoif";s:10:"resolution";i:2;s:5:"style";s:5:"small";}',`position`='15';
 


