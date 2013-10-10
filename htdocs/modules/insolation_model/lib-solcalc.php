<?php


//solar position code ported from the javascript at http://www.esrl.noaa.gov/gmd/grad/solcalc/

class Solcalc {
	
	var $vars= array();
	
	function calculate($lat,$lng,$datetime) {
	
		//seperate iso date
		$stamp= strtotime($datetime);
		$year =	date("Y",$stamp);
		$month = date("m",$stamp);
		$day =	date("d",$stamp);
		$hr =    date("H",$stamp);
		$min =   date("i",$stamp);
		$sec =   date("s",$stamp);
		$dst =   date("I",$stamp);
		$tz =    date('Z',$stamp)/3600; 
		
		if ($dst) $tz--; //tz will double dst otherwise
		
		//convert to julian time
		$jday = $this->getJD($year,$month,$day);
		$tl =   $this->getTimeLocal($hr,$min,$sec,$dst);
		$total = $jday + $tl/1440.0 - $tz/24.0;
		$T = $this->calcTimeJulianCent($total);
		
		//do calcs
		$this->calcAzEl(1, $T, $tl, $lat, $lng, $tz);
		$this->calcSolNoon($jday, $lng, $tz, $dst);
		$this->calcSunriseSet(1, $jday, $lat, $lng, $tz, $dst);
		$this->calcSunriseSet(0, $jday, $lat, $lng, $tz, $dst);
		
		$this->vars['az']= number_format($this->vars['az'],2);
		$this->vars['ze']= number_format($this->vars['ze'],2);

		return $this->vars;
	}

	function getJD($docyear,$docmonth,$docday){

		if ($docmonth <= 2) {
			$docyear -= 1;
			$docmonth += 12;
		}
		$A = floor($docyear/100);
		$B = 2 - $A + floor($A/4);
		$JD = floor(365.25*($docyear + 4716)) + floor(30.6001*($docmonth+1)) + $docday + $B - 1524.5;
		return $JD;
	}

	function getTimeLocal($dochr,$docmn,$docsc,$docdst) {

		if ($docdst) $dochr -= 1;
		$mins = $dochr * 60 + $docmn + $docsc/60.0;
		return $mins;
	}

	function calcAzEl($output, $T, $localtime, $latitude, $longitude, $zone){

		$eqTime = $this->calcEquationOfTime($T);
		$theta=   $this->calcSunDeclination($T);
		
		//store eqt and sd 
		$this->vars['eqt']= floor($eqTime*100 +0.5)/100.0;
		$this->vars['sd']=  floor($theta*100+0.5)/100.0;
		
		$solarTimeFix = $eqTime + 4.0 * $longitude - 60.0 * $zone;
		$earthRadVec = $this->calcSunRadVector($T);
		$trueSolarTime = $localtime + $solarTimeFix;
		while ($trueSolarTime > 1440) {
			$trueSolarTime -= 1440;
		}
		$hourAngle = $trueSolarTime / 4.0 - 180.0;
		if ($hourAngle < -180) $hourAngle += 360.0;
		$haRad = deg2rad($hourAngle);
		$csz = sin(deg2rad($latitude)) * sin(deg2rad($theta)) + cos(deg2rad($latitude)) * cos(deg2rad($theta)) * cos($haRad);
		if ($csz > 1.0)       $csz = 1.0; 
		else if ($csz < -1.0) $csz = -1.0;
		$zenith = rad2deg(acos($csz));
		$azDenom = ( cos(deg2rad($latitude)) * sin(deg2rad($zenith)) );
		if (abs($azDenom) > 0.001) {
			$azRad = (( sin(deg2rad($latitude)) * cos(deg2rad($zenith)) ) - sin(deg2rad($theta))) / $azDenom;
			if (abs($azRad) > 1.0) {
				if ($azRad < 0) $azRad = -1.0;
				else 	          $azRad = 1.0;
			}
			$azimuth = 180.0 - rad2deg(acos($azRad));
			if ($hourAngle > 0.0)  $azimuth = -$azimuth;
		}
		else {
			if ($latitude > 0.0) $azimuth = 180.0;
			else 	               $azimuth = 0.0;
		}
		if ($azimuth < 0.0) 	$azimuth += 360.0;
		$exoatmElevation = 90.0 - $zenith;

		//Atmospheric Refraction correction
		if ($exoatmElevation > 85.0) {
			$refractionCorrection = 0.0;
		} 
		else {
			$te = tan (deg2rad($exoatmElevation));
			if ($exoatmElevation > 5.0) {
				$refractionCorrection = 58.1 / $te - 0.07 / ($te*$te*$te) + 0.000086 / ($te*$te*$te*$te*$te);
			}
			else if ($exoatmElevation > -0.575) {
				$refractionCorrection = 1735.0 + $exoatmElevation * (-518.2 + $exoatmElevation * (103.4 + $exoatmElevation * (-12.79 + $exoatmElevation * 0.711) ) );
			}
			else {
				$refractionCorrection = -20.774 / $te;
			}
			$refractionCorrection = $refractionCorrection / 3600.0;
		}

		$solarZen = $zenith - $refractionCorrection;

		//if ((output) && (solarZen > 108.0) ) {
		//	document.getElementById("az").value = "dark"
		//	document.getElementById("el").value = "dark"
		//}
		
		$this->vars['az']= $azimuth;
		$this->vars['ze']= $solarZen;
	}
	
	function calcSolNoon($jd, $longitude, $timezone, $dst){

		$tnoon = $this->calcTimeJulianCent($jd - $longitude/360.0);
		$eqTime = $this->calcEquationOfTime($tnoon);
		$solNoonOffset = 720.0 - ($longitude * 4) - $eqTime; // in minutes
		$newt = $this->calcTimeJulianCent($jd + $solNoonOffset/1440.0);
		$eqTime = $this->calcEquationOfTime($newt);
		$solNoonLocal = 720 - ($longitude * 4) - $eqTime + ($timezone*60.0);// in minutes
		if ($dst) $solNoonLocal += 60.0;
		while ($solNoonLocal < 0.0) {
			$solNoonLocal += 1440.0;
		}
		while ($solNoonLocal >= 1440.0) {
			$solNoonLocal -= 1440.0;
		}
		$this->vars['solnoon'] = $this->timeString($solNoonLocal, 3);
	}
	
	function calcSunriseSetUTC($rise, $JD, $latitude, $longitude){

		$t = $this->calcTimeJulianCent($JD);
		$eqTime = $this->calcEquationOfTime($t);
		$solarDec = $this->calcSunDeclination($t);
		$hourAngle = $this->calcHourAngleSunrise($latitude, $solarDec);
		
		if (!$rise) $hourAngle = -$hourAngle;
		$delta = $longitude + rad2deg($hourAngle);
		$timeUTC = 720 - (4.0 * $delta) - $eqTime;	// in minutes
		return $timeUTC;
	}

	function calcSunriseSet($rise, $JD, $latitude, $longitude, $timezone, $dst) {
		// rise = 1 for sunrise, 0 for sunset
		$id = $rise ? "sunrise" : "sunset";

		$timeUTC =    $this->calcSunriseSetUTC($rise, $JD, $latitude, $longitude);
		$newTimeUTC = $this->calcSunriseSetUTC($rise, $JD + $timeUTC/1440.0, $latitude, $longitude);
		
		//most cases 
		if ($this->isNumber($newTimeUTC)) {
			
			$timeLocal = $newTimeUTC + ($timezone * 60.0);
			$timeLocal+= (($dst) ? 60.0 : 0.0);
			if ( ($timeLocal >= 0.0) && ($timeLocal < 1440.0) ) {
				$this->vars[$id] = $this->timeString($timeLocal,2);
			} 
			
			//if not today add the date, unlikely
			else	{
				$jday = $JD;
				$increment = (($timeLocal < 0) ? 1 : -1);
				while (($timeLocal < 0.0)||($timeLocal >= 1440.0)) {
					$timeLocal += $increment * 1440.0;
					$jday -= $increment;
				}
				$this->vars[$id] = $this->timeDateString($jday,$timeLocal);
			}
		}
		
		//handle edge cases
		else { // no sunrise/set found
			$doy = calcDoyFromJD($JD);
			if ( (($latitude > 66.4) && ($doy > 79) && ($doy < 267)) ||	(($latitude < -66.4) && (($doy < 83) || ($doy > 263))) ) {
			   //previous sunrise/next sunset
				if ($rise) { // find previous sunrise
					$jdy = $this->calcJDofNextPrevRiseSet(0, $rise, $JD, $latitude, $longitude, $timezone, $dst);
				}
				else { // find next sunset
					$jdy = $this->calcJDofNextPrevRiseSet(1, $rise, $JD, $latitude, $longitude, $timezone, $dst);
				}
				$this->vars[$id] = $this->dayString($jdy,0,3);
			}
			else {	 //previous sunset/next sunrise
				if ($rise == 1) { // find previous sunrise
					$jdy = $this->calcJDofNextPrevRiseSet(1,$ $rise, $JD, $latitude, $longitude, $timezone, $dst);
				}
				else { // find next sunset
					$jdy = $this->calcJDofNextPrevRiseSet(0, $rise, JD, $latitude, $longitude, $timezone, $dst);
				}
				$this->vars[$id] = $this->dayString($jdy,0,3);
			}
		}
	}
	

	function calcJDofNextPrevRiseSet($next, $rise, $JD, $latitude, $longitude, $tz, $dst){

		$julianday = $JD;
		$increment = (($next) ? 1.0 : -1.0);

		$time = $this->calcSunriseSetUTC($rise, $julianday, $latitude, $longitude);
		while(!isNumber($time)){
			$julianday += $increment;
			$time = $this->calcSunriseSetUTC($rise, $julianday, $latitude, $longitude);
		}
		$timeLocal = $time + $tz * 60.0 + (($dst) ? 60.0 : 0.0);
		while (($timeLocal < 0.0) || ($timeLocal >= 1440.0)) {
			$incr = (($timeLocal < 0) ? 1 : -1);
			$timeLocal += ($incr * 1440.0);
			$julianday -= $incr;
		}
		return $julianday;
	}

	function calcTimeJulianCent($jd){
		$T = ($jd - 2451545.0) / 36525.0;
		return $T;
	}

	function calcJDFromJulianCent($t){
		$JD = $t * 36525.0 + 2451545.0;
		return $JD;
	}

	function isLeapYear($yr){
		return (($yr % 4 == 0 && $yr % 100 != 0) || $yr % 400 == 0); //more brackets?
	}

	function calcDoyFromJD($jd){

		$z = floor($jd + 0.5);
		$f = ($jd + 0.5) - $z;
		if ($z < 2299161) {
			$A = $z;
		} 
		else {
			$alpha = floor(($z - 1867216.25) / 36524.25);
			$A = $z + 1 + $alpha - floor($alpha/4);
		}
		$B = $A + 1524;
		$C = floor(($B - 122.1)/365.25);
		$D = floor(365.25 * $C);
		$E = floor(($B - $D)/30.6001);
		$day = $B - $D - floor(30.6001 * $E) + f;
		$month = ($E < 14) ? $E - 1 : $E - 13;
		$year = ($month > 2) ? $C - 4716 : $C - 4715;

		$k = $this->isLeapYear($year) ? 1 : 2;
		$doy = floor((275 * $month)/9) - $k * floor(($month + 9)/12) + $day -30;
		return $doy;
	}


	function calcGeomMeanLongSun($t){
		$L0 = 280.46646 + $t * (36000.76983 + $t*(0.0003032));
		while ($L0 > 360.0) {
			$L0 -= 360.0;
		}
		while ($L0 < 0.0) {
			$L0 += 360.0;
		}
		return $L0;		// in degrees
	}

	function calcGeomMeanAnomalySun($t){
		$M = 357.52911 + $t * (35999.05029 - 0.0001537 * $t);
		return $M;		// in degrees
	}

	function calcEccentricityEarthOrbit($t){

		$e = 0.016708634 - $t * (0.000042037 + 0.0000001267 * $t);
		return $e;		// unitless
	}

	function calcSunEqOfCenter($t){
		$m = $this->calcGeomMeanAnomalySun($t);
		$mrad = deg2rad($m);
		$sinm = sin($mrad);
		$sin2m = sin($mrad+$mrad);
		$sin3m = sin($mrad+$mrad+$mrad);
		$C = $sinm * (1.914602 - $t * (0.004817 + 0.000014 * $t)) + $sin2m * (0.019993 - 0.000101 * $t) + $sin3m * 0.000289;
		return $C;		// in degrees
	}

	function calcSunTrueLong($t){
		$l0 = $this->calcGeomMeanLongSun($t);
		$c = $this->calcSunEqOfCenter($t);
		$O = $l0 + $c;
		return $O;		// in degrees
	}

	function calcSunTrueAnomaly($t){

		$m = $this->calcGeomMeanAnomalySun($t);
		$c = $this->calcSunEqOfCenter($t);
		$v = $m + $c;
		return $v;		// in degrees
	}

	function calcSunRadVector($t){

		$v = $this->calcSunTrueAnomaly($t);
		$e = $this->calcEccentricityEarthOrbit($t);
		$R = (1.000001018 * (1 - $e * $e)) / (1 + $e * cos(deg2rad($v)));
		return $R;		// in AUs
	}

	function calcSunApparentLong($t){
		$o = $this->calcSunTrueLong($t);
		$omega = 125.04 - 1934.136 * $t;
		$lambda = $o - 0.00569 - 0.00478 * sin(deg2rad($omega));
		return $lambda;		// in degrees
	}

	function calcMeanObliquityOfEcliptic($t){
		$seconds = 21.448 - $t * (46.8150 + $t * (0.00059 - $t*(0.001813)));
		$e0 = 23.0 + (26.0 + ($seconds/60.0))/60.0;
		return $e0;		// in degrees
	}

	function calcObliquityCorrection($t){

		$e0 = $this->calcMeanObliquityOfEcliptic($t);
		$omega = 125.04 - 1934.136 * $t;
		$e = $e0 + 0.00256 * cos(deg2rad($omega));
		return $e;		// in degrees
	}

	function calcSunRtAscension($t){
		$e = $this->calcObliquityCorrection($t);
		$lambda = $this->calcSunApparentLong($t);
		$tananum = (cos(deg2rad($e)) * sin(deg2rad($lambda)));
		$tanadenom = (cos(deg2rad($lambda)));
		$alpha = rad2deg(atan2($tananum, $tanadenom));
		return $alpha;		// in degrees
	}

	function calcSunDeclination($t){

		$e = $this->calcObliquityCorrection($t);
		$lambda = $this->calcSunApparentLong($t);

		$sint = sin(deg2rad($e)) * sin(deg2rad($lambda));
		$theta = rad2deg(asin($sint));
		return $theta;		// in degrees
	}

	function calcEquationOfTime($t){

		$epsilon = $this->calcObliquityCorrection($t);
		$l0 = $this->calcGeomMeanLongSun($t);
		$e = $this->calcEccentricityEarthOrbit($t);
		$m = $this->calcGeomMeanAnomalySun($t);

		$y = tan(deg2rad($epsilon)/2.0);
		$y *= $y;

		$sin2l0 = sin(2.0 * deg2rad($l0));
		$sinm	 = sin(deg2rad($m));
		$cos2l0 = cos(2.0 * deg2rad($l0));
		$sin4l0 = sin(4.0 * deg2rad($l0));
		$sin2m	= sin(2.0 * deg2rad($m));

		$Etime = $y * $sin2l0 - 2.0 * $e * $sinm + 4.0 * $e * $y * $sinm * $cos2l0 - 0.5 * $y * $y * $sin4l0 - 1.25 * $e * $e * $sin2m; //brackets
		return rad2deg($Etime)*4.0;	// in minutes of time
	}

	function calcHourAngleSunrise($lat, $solarDec){

		$latRad = deg2rad($lat);
		$sdRad	= deg2rad($solarDec);
		$HAarg = (cos(deg2rad(90.833))/(cos($latRad)*cos($sdRad)) - tan($latRad) * tan($sdRad));
		$HA = acos($HAarg);
		return $HA;		// in radians (for sunset, use -HA)
	}

	function isNumber($inputVal){

		$oneDecimal = false;
		$inputStr = (string)$inputVal;
		for ($i = 0; $i < strlen($inputStr); $i++) {
			$oneChar = substr($inputStr,$i,1);           
			if ($i == 0 && ($oneChar == "-" || $oneChar == "+")) {
				continue;
			}
			if ($oneChar == "." && !$oneDecimal) {
				$oneDecimal = true;
				continue;
			}
			if ($oneChar < "0" || $oneChar > "9") return false;
		}
		return true;
	}

	function zeroPad($n, $digits) {
		$n = (string)$n;
		while (strlen($n) < $digits) {
			
			$n = '0' . $n;
		}
		return $n;
	}

	function dayString($jd, $next, $flag){
	
		//only ever calls 0,2 or 0,3

		// returns a string in the form DDMMMYYYY[ next] to display prev/next rise/set
		// flag=2 for DD MMM, 3 for DD MM YYYY, 4 for DDMMYYYY next/prev
		if ( ($jd < 900000) || ($jd > 2817000) ) {
			$output = "error";
		}
		else {
			$z = floor($jd + 0.5);
			$f = ($jd + 0.5) - $z;
			if ($z < 2299161) {
				$A = $z;
			} 
			else {
				$alpha = floor(($z - 1867216.25)/36524.25);
				$A = $z + 1 + $alpha - floor($alpha/4);
			}
			$B = $A + 1524;
			$C = floor(($B - 122.1)/365.25);
			$D = floor(365.25 * $C);
			$E = floor(($B - $D)/30.6001);
			$day = $B - $D - floor(30.6001 * $E) + $f;
			$month = ($E < 14) ? $E - 1 : $E - 13;
			$year = (($month > 2) ? $C - 4716 : $C - 4715);

			if ($flag== 2) $output = zeroPad(day,2) . " " . $this->monthList($month);
			if ($flag== 3) $output = zeroPad(day,2) . $this->monthList($month) . year.toString();
			if ($flag== 4) $output = zeroPad(day,2) . $this->monthList($month) . year.toString() . (($next) ? " next" : " prev");
		}
		return output;
	}

	function timeDateString($JD, $minutes){

		$output = $this->timeString($minutes, 2) . " " . $this->dayString($JD, 0, 2);
		return $output;
	}

	function timeString($minutes, $flag) {
		// timeString returns a zero-padded string (HH:MM:SS) given time in minutes
		// flag=2 for HH:MM, 3 for HH:MM:SS


		if ( ($minutes >= 0) && ($minutes < 1440) ) {


			$floatHour = $minutes / 60.0;
			$hour = floor($floatHour);
			$floatMinute = 60.0 * ($floatHour - floor($floatHour));
			$minute = floor($floatMinute);
			$floatSec = 60.0 * ($floatMinute - floor($floatMinute));
			$second = floor($floatSec + 0.5);
			if ($second > 59) {
				$second = 0;
				$minute += 1;
			}
			if (($flag == 2) && ($second >= 30)) $minute++;
			if ($minute > 59) {
				$minute = 0;
				$hour += 1;
			}

			
			$output = $this->zeroPad($hour,2) . ":" . $this->zeroPad($minute,2);

			
			//if ($flag > 2) $output = $output . ":" . $this->zeroPad($second,2);
		}
		else {
			$output = "error";
		}
		return $output;
	}

	function monthList($month) {
		return date("M",  mktime(0, 0, 0, 1, $month, 2013));
	}
	
	//class ends
}

?>