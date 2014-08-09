


######################################################################
# VIEW TABLES
######################################################################


#BLACKBOXVIEWS
#User defined view definitions

create table blackboxviews (
	id_view      int unsigned primary key auto_increment,
	viewname     varchar(255) not null,
	template     varchar(255) not null,
	type         char(1) not null,
	settings     text not null,
	position     tinyint unsigned not null
);
INSERT INTO blackboxviews SET `id_view`='1', template= 'template-view1.html', `position`='1';
INSERT INTO blackboxviews SET `id_view`='2', template= 'template-view2.html', `position`='2';


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

# these arent necessary but will get the default view running until the default self populates
INSERT INTO blackboxelements SET `id_element`='1',`id_view`='1',`name`='Energy',`type`='d',`panetag`='Top',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:7:"whtotal";s:10:"resolution";s:1:"0";s:5:"style";s:5:"large";}',`position`='1';
INSERT INTO blackboxelements SET `id_element`='2',`id_view`='1',`name`='Stage',`type`='d',`panetag`='Top',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:9:"stageword";s:10:"resolution";s:0:"";s:5:"style";s:5:"large";}',`position`='3';
INSERT INTO blackboxelements SET `id_element`='3',`id_view`='1',`name`='Vbat',`type`='d',`panetag`='Top',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:4:"vout";s:10:"resolution";s:1:"1";s:5:"style";s:5:"large";}',`position`='3';
INSERT INTO blackboxelements SET `id_element`='4',`id_view`='1',`name`='Solar',`type`='g',`panetag`='Right',`settings`='a:8:{s:5:"width";i:780;s:6:"height";i:230;s:4:"ymax";i:1800;s:7:"average";i:1;s:9:"linethick";i:2;s:10:"linesmooth";i:1;s:7:"datapts";a:4:{i:1;a:5:{s:6:"module";s:16:"insolation_model";s:9:"datapoint";s:6:"power1";s:9:"linecolor";s:5:"green";s:10:"multiplier";d:1;s:4:"name";s:3:"Sun";}i:2;a:5:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:4:"pout";s:9:"linecolor";s:4:"blue";s:10:"multiplier";d:1;s:4:"name";s:12:"Power in (W)";}i:3;a:5:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:5:"pload";s:9:"linecolor";s:6:"orange";s:10:"multiplier";d:1;s:4:"name";s:13:"Power out (W)";}i:4;a:5:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:8:"stagelin";s:9:"linecolor";s:3:"red";s:10:"multiplier";d:200;s:4:"name";s:5:"Stage";}}s:4:"ymin";i:0;}',`position`='1';
INSERT INTO blackboxelements SET `id_element`='7',`id_view`='1',`name`='Iout',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:4:"iout";s:10:"resolution";i:1;s:5:"style";s:6:"medium";}',`position`='2';
INSERT INTO blackboxelements SET `id_element`='8',`id_view`='1',`name`='Imax',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:7:"maxiout";s:10:"resolution";s:1:"1";s:5:"style";s:6:"medium";}',`position`='3';
INSERT INTO blackboxelements SET `id_element`='9',`id_view`='1',`name`='Vmax',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:7:"maxvbat";s:10:"resolution";i:1;s:5:"style";s:6:"medium";}',`position`='5';
INSERT INTO blackboxelements SET `id_element`='10',`id_view`='1',`name`='Absorb',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:9:"durabsorb";s:10:"resolution";i:1;s:5:"style";s:6:"medium";}',`position`='5';
INSERT INTO blackboxelements SET `id_element`='11',`id_view`='1',`name`='Float',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:8:"durfloat";s:10:"resolution";i:1;s:5:"style";s:6:"medium";}',`position`='6';
INSERT INTO blackboxelements SET `id_element`='13',`id_view`='1',`name`='Vpv',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:3:"vpv";s:10:"resolution";i:1;s:5:"style";s:6:"medium";}',`position`='4';
INSERT INTO blackboxelements SET `id_element`='15',`id_view`='1',`name`='Battery',`type`='g',`panetag`='Right',`settings`='a:8:{s:5:"width";i:780;s:6:"height";i:220;s:4:"ymax";i:35;s:7:"average";i:1;s:9:"linethick";i:2;s:10:"linesmooth";i:1;s:7:"datapts";a:5:{i:1;a:5:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:4:"vout";s:9:"linecolor";s:4:"teal";s:4:"name";s:18:"Battey Voltage (V)";s:10:"multiplier";d:1;}i:2;a:5:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:4:"tbat";s:9:"linecolor";s:6:"orange";s:4:"name";s:16:"Battery Temp (C)";s:10:"multiplier";d:1;}i:3;a:5:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:3:"soc";s:9:"linecolor";s:5:"green";s:10:"multiplier";d:0.34999999999999998;s:4:"name";s:10:"SOC %/0.35";}i:4;a:5:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:7:"ichgbat";s:9:"linecolor";s:4:"blue";s:10:"multiplier";d:0.5;s:4:"name";s:12:"Charge (A/2)";}i:5;a:5:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:7:"idisbat";s:9:"linecolor";s:3:"red";s:10:"multiplier";d:0.5;s:4:"name";s:15:"Discharge (A/2)";}}s:4:"ymin";i:0;}',`position`='2';
INSERT INTO blackboxelements SET `id_element`='16',`id_view`='1',`name`='Vmin',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:7:"minvbat";s:10:"resolution";i:1;s:5:"style";s:6:"medium";}',`position`='4';
INSERT INTO blackboxelements SET `id_element`='20',`id_view`='1',`name`='Time',`type`='d',`panetag`='Top',`settings`='a:4:{s:6:"module";s:16:"insolation_model";s:9:"datapoint";s:4:"time";s:10:"resolution";i:1;s:5:"style";s:5:"large";}',`position`='4';
INSERT INTO blackboxelements SET `id_element`='21',`id_view`='1',`name`='Az',`type`='d',`panetag`='Bottom',`settings`='a:4:{s:6:"module";s:16:"insolation_model";s:9:"datapoint";s:2:"az";s:10:"resolution";s:1:"0";s:5:"style";s:5:"small";}',`position`='10';
INSERT INTO blackboxelements SET `id_element`='22',`id_view`='1',`name`='Zen',`type`='d',`panetag`='Bottom',`settings`='a:4:{s:6:"module";s:16:"insolation_model";s:9:"datapoint";s:2:"ze";s:10:"resolution";s:1:"0";s:5:"style";s:5:"small";}',`position`='11';
INSERT INTO blackboxelements SET `id_element`='23',`id_view`='1',`name`='Am',`type`='d',`panetag`='Bottom',`settings`='a:4:{s:6:"module";s:16:"insolation_model";s:9:"datapoint";s:2:"am";s:10:"resolution";i:1;s:5:"style";s:5:"small";}',`position`='12';
INSERT INTO blackboxelements SET `id_element`='24',`id_view`='1',`name`='Aoi',`type`='d',`panetag`='Bottom',`settings`='a:4:{s:6:"module";s:16:"insolation_model";s:9:"datapoint";s:3:"aoi";s:10:"resolution";s:1:"0";s:5:"style";s:5:"small";}',`position`='13';
INSERT INTO blackboxelements SET `id_element`='29',`id_view`='2',`name`='Vbat',`type`='d',`panetag`='Vbat',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:4:"vout";s:10:"resolution";i:1;s:5:"style";s:5:"large";}',`position`='1';
INSERT INTO blackboxelements SET `id_element`='30',`id_view`='2',`name`='Vpv',`type`='d',`panetag`='Vpv',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:3:"vpv";s:10:"resolution";s:1:"0";s:5:"style";s:6:"medium";}',`position`='1';
INSERT INTO blackboxelements SET `id_element`='31',`id_view`='2',`name`='Pin',`type`='d',`panetag`='Cc',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:4:"pout";s:10:"resolution";s:1:"0";s:5:"style";s:5:"large";}',`position`='1';
INSERT INTO blackboxelements SET `id_element`='32',`id_view`='2',`name`='Wh',`type`='d',`panetag`='Cc',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:7:"whtotal";s:10:"resolution";s:1:"0";s:5:"style";s:6:"medium";}',`position`='2';
INSERT INTO blackboxelements SET `id_element`='33',`id_view`='2',`name`='Icc',`type`='d',`panetag`='Icc',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:4:"iout";s:10:"resolution";i:1;s:5:"style";s:6:"medium";}',`position`='1';
INSERT INTO blackboxelements SET `id_element`='34',`id_view`='2',`name`='Tbat',`type`='d',`panetag`='Vbat',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:4:"tbat";s:10:"resolution";i:1;s:5:"style";s:5:"small";}',`position`='4';
INSERT INTO blackboxelements SET `id_element`='35',`id_view`='2',`name`='Ipv',`type`='d',`panetag`='Ipv',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:3:"ipv";s:10:"resolution";i:1;s:5:"style";s:6:"medium";}',`position`='1';
INSERT INTO blackboxelements SET `id_element`='36',`id_view`='2',`name`='Tcc',`type`='d',`panetag`='Cc',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:3:"tcc";s:10:"resolution";i:1;s:5:"style";s:5:"small";}',`position`='4';
INSERT INTO blackboxelements SET `id_element`='37',`id_view`='2',`name`='Stage',`type`='d',`panetag`='Cc',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:9:"stageword";s:10:"resolution";s:0:"";s:5:"style";s:6:"medium";}',`position`='3';
INSERT INTO blackboxelements SET `id_element`='47',`id_view`='2',`name`='Ibat',`type`='d',`panetag`='Ibat',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:4:"ibat";s:10:"resolution";i:1;s:5:"style";s:6:"medium";}',`position`='0';
INSERT INTO blackboxelements SET `id_element`='48',`id_view`='2',`name`='Iload',`type`='d',`panetag`='Iload',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:5:"iload";s:10:"resolution";i:1;s:5:"style";s:6:"medium";}',`position`='0';
INSERT INTO blackboxelements SET `id_element`='49',`id_view`='2',`name`='Pload',`type`='d',`panetag`='Load',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:5:"pload";s:10:"resolution";s:1:"0";s:5:"style";s:5:"large";}',`position`='1';
INSERT INTO blackboxelements SET `id_element`='50',`id_view`='2',`name`='Whload',`type`='d',`panetag`='Load',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:6:"whload";s:10:"resolution";s:1:"0";s:5:"style";s:6:"medium";}',`position`='2';
INSERT INTO blackboxelements SET `id_element`='51',`id_view`='2',`name`='Batstate',`type`='d',`panetag`='Vbat2',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:8:"batstate";s:10:"resolution";s:0:"";s:5:"style";s:5:"small";}',`position`='3';
INSERT INTO blackboxelements SET `id_element`='56',`id_view`='2',`name`='SOC',`type`='d',`panetag`='Vbat2',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:3:"soc";s:10:"resolution";s:1:"0";s:5:"style";s:6:"medium";}',`position`='1';
INSERT INTO blackboxelements SET `id_element`='58',`id_view`='1',`name`='SOC',`type`='d',`panetag`='Top',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:3:"soc";s:10:"resolution";s:1:"0";s:5:"style";s:5:"large";}',`position`='2';
INSERT INTO blackboxelements SET `id_element`='59',`id_view`='1',`name`='Ibat',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:4:"ibat";s:10:"resolution";i:1;s:5:"style";s:6:"medium";}',`position`='2';
INSERT INTO blackboxelements SET `id_element`='60',`id_view`='1',`name`='DSF',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:9:"lastfloat";s:10:"resolution";s:1:"0";s:5:"style";s:6:"medium";}',`position`='10';
INSERT INTO blackboxelements SET `id_element`='61',`id_view`='1',`name`='Sunrise',`type`='d',`panetag`='Bottom',`settings`='a:4:{s:6:"module";s:16:"insolation_model";s:9:"datapoint";s:7:"sunrise";s:10:"resolution";s:0:"";s:5:"style";s:5:"small";}',`position`='0';
INSERT INTO blackboxelements SET `id_element`='62',`id_view`='1',`name`='Sunset',`type`='d',`panetag`='Bottom',`settings`='a:4:{s:6:"module";s:16:"insolation_model";s:9:"datapoint";s:6:"sunset";s:10:"resolution";s:0:"";s:5:"style";s:5:"small";}',`position`='0';
INSERT INTO blackboxelements SET `id_element`='63',`id_view`='1',`name`='Solar Noon',`type`='d',`panetag`='Bottom',`settings`='a:4:{s:6:"module";s:16:"insolation_model";s:9:"datapoint";s:7:"solnoon";s:10:"resolution";s:0:"";s:5:"style";s:5:"small";}',`position`='0';
INSERT INTO blackboxelements SET `id_element`='64',`id_view`='1',`name`='up',`type`='d',`panetag`='Left',`settings`='a:4:{s:6:"module";s:15:"midnite_classic";s:9:"datapoint";s:6:"uptime";s:10:"resolution";i:1;s:5:"style";s:6:"medium";}',`position`='20';
 

