<?php
// ***************************************************************************
   require('global.inc.php');
   require('miner.inc.php');
// ***************************************************************************
   function secondsToWords($seconds){
     $ret = "";
     /*** get the days ***/
     $days = intval(intval($seconds) / (3600*24));
     if($days> 0){
        $ret .= "$days<small> day </small>";
     }
     /*** get the hours ***/
     $hours = (intval($seconds) / 3600) % 24;
     if($hours > 0){
        $ret .= "$hours<small> hr </small>";
     }
     /*** get the minutes ***/
     $minutes = (intval($seconds) / 60) % 60;
     if($minutes > 0){
        $ret .= "$minutes<small> min </small>";
     }
     /*** get the seconds ***/
     $seconds = intval($seconds) % 60;
     if ($seconds > 0){
         $ret .= "$seconds<small> sec</small>";
     }
     return $ret;
   }
// ***************************************************************************
// hashrate averages
// ***************************************************************************
   $summary = miner('summary', '');
   foreach($summary as $key => $value) {
     if(is_array($value) && $key=="SUMMARY") {
        foreach($value as $key_ => $value_){
           if(is_array($value_)) {
             foreach($value_ as $key__ => $value__){ 
	        if($key__=='Elapsed') {
	    	   $output['CG_Uptime'] = $value__;
                }
	        if($key__=='MHSav') {
	 	   $output['MHS_Avg'] = $value__;
                }
	        if($key__=='MHS5s') {
	 	   $output['MHS_5s'] = $value__;
                }
	        if($key__=='MHS1m') {
	 	   $output['MHS_1m'] = $value__;
                }
	        if($key__=='MHS5m') {
	 	   $output['MHS_5m'] = $value__;
                }
	        if($key__=='MHS15m') {
	 	   $output['MHS_15m'] = $value__;
                }
              }
           }
        }
     }
   }
// ***************************************************************************
// current hashrate and temperatures
// ***************************************************************************
   if(file_exists("/var/run/mg_rate_temp")){
	$mpTemp = explode(" ", file_get_contents("/var/run/mg_rate_temp"));
   } else {
	$mpTemp = array(0, 0, 0, 0);
   }
   
   $output['MHS_Now'] = trim($mpTemp[0]);
   $output['DegC_In'] = trim($mpTemp[1]);
   $output['DegC_TopOut'] = trim($mpTemp[2]);
   $output['DegC_BotOut'] = trim($mpTemp[3]);

// ***************************************************************************
// collect miner data
// ***************************************************************************
   $output['UnitID'] = $model_id;
   $hostname = trim(exec("hostname"));
   $output['UnitSN'] = str_replace("miner-","",$hostname);
   $output['UnitFW'] = trim(file_get_contents(CURRENT_VERSION_FILE));
   $output['UnitMA'] = trim(exec("/usr/local/bin/getmac.sh"));
   $output['Unit_Uptime'] = round($uptime[0]);
   $output['FreeMem'] = exec('awk \'/MemFree/ {printf( "%.2d\n", $2 / 1024 )}\' /proc/meminfo');

// ***************************************************************************
// collect power usage data
// ***************************************************************************
   if(file_exists('/tmp/voltage')) $volt = explode('/',trim(exec('cat /tmp/voltage')));
   $crt=0;
   foreach($volt as $key => $value) {
     if($crt==0) { 
        $output['PSUVolt_Top'] = $value; 
        $crt=1; 
     } else { 
        $output['PSUVolt_Bot'] = $value; 
     }
   }

   $wm_array = explode(' ', trim(trim(file_get_contents(MINER_WORKMODE_FILE))));
   foreach($wm_array as $key => $value) {
	$exp_value = explode(':',$value);
	$output[$exp_value[0]] = $exp_value[1];
   }

   $psu_data_top = exec('cat /tmp/asics | grep lim='.$output['AC_TOP']);
   $psu_top = explode("->",strstr(strstr($psu_data_top," "),"w",true));
   $output['PSUWall_Top'] = trim($psu_top[0]); 
   $output['PSUWatt_Top'] = trim($psu_top[1]); 

   $psu_data_bot = exec('cat /tmp/asics | grep lim='.$output['AC_BOT']);
   $psu_bot = explode("->",strstr(strstr($psu_data_bot," "),"w",true));
   $output['PSUWall_Bot'] = trim($psu_bot[0]); 
   $output['PSUWatt_Bot'] = trim($psu_bot[1]);

   $mpCPULoad = sys_getloadavg(); 
   foreach($mpCPULoad as $key => $value) {
      $output['UnitCPULd_'.$key] = $value;
   }

// ***************************************************************************
// output results
// ***************************************************************************
   header("Content-type: application/json");
   echo json_encode($output);
// ***************************************************************************
?>

