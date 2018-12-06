<?php
global $base, $conf, $baseURL, $images;

$title_right_html = '';
$title_left_html  = '';
$modbodyhtml = '';
$modjs = '';

// Get info about this file name
$onainstalldir = dirname($base);
$file = str_replace($onainstalldir.'/www', '', __FILE__);
$thispath = dirname($file);

// future config options
$boxheight = '300px';
$divid = 'vminfo';

// Display only on the host display
if (stristr($record['devicefull'],'VMware') === FALSE) {
} else {
  if ($extravars['window_name'] == 'display_host') {

    if($extravars['window_name'] == 'display_host') { $boxheight = '150px'; $divid = 'hostvminfo'; }

    $title_left_html .= <<<EOL
        &nbsp;VMware info&nbsp;&nbsp;
EOL;

    $title_right_html .= <<<EOL
        <span id="vspherelinks"></span><a title="Reload VMware info" onclick="el('vspherelinks').innerHTML = '';el('{$divid}').innerHTML = '<center>Reloading...</center>';xajax_window_submit('{$file}', xajax.getFormValues('vminfo_form'), 'display_stats');"><img src="{$images}/silk/arrow_refresh.png" border="0"></a>
EOL;

    $modbodyhtml .= <<<EOL
<form id="vminfo_form" onSubmit="return false;">
<input type="hidden" name="divname" value="{$divid}">
<input type="hidden" name="host_name" value="{$record['fqdn']}">
</form>
<div id="{$divid}" style="height: {$boxheight};overflow-y: auto;overflow-x:hidden;font-size:small">
<center><img src="{$images}/loading.gif"></center><br>
</div>
EOL;

    // run the function that will update the content of the plugin. update it every 5 mins
    $modjs = "xajax_window_submit('{$file}', xajax.getFormValues('vminfo_form'), 'display_stats');";

$divid='';

  }
}




/*
Using curl, gather all of the data from supplied urls and format them in a table.
Then update the vmwareinfo innerHTML with the data.
*/
function ws_display_stats($window_name, $form='') {
    global $conf, $self, $onadb, $onabase, $base, $images, $baseURL;

    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);

    // Get our Vmware sdk class
    require_once('vmwaresdk.php');

    // Pull in config file data
    $vmconffile = (file_exists($onabase.'/etc/vmware_stats.conf.php')) ? $onabase.'/etc/vmware_stats.conf.php' : dirname(__FILE__).'/vmware_stats.conf.php';
    if (file_exists($vmconffile)) {
        @require_once($vmconffile);
        if (!isset($vmlogin[0]['server'])) {
            $htmllines .= <<<EOL
                No VCenter servers defined via config file.<br>
EOL;
        }

        // search patterns are:
        // 1. use vmware findallbydnsname function using FQDN from ONA.
        // 2. look for FQDN as the 'name' of the vhost
        // 3. use find fuction using just the host portion of the name (part up to first dot)
        // yep there are probably much better ways of doing it.. this gets the majority of what I need for now
        // bottom line is, you need ot use the same names in both for best results or have vmtools installed.
        foreach ( $vmlogin as $vmconnect ) {
          // Save which server this is for later
          $vmserver = $vmconnect['server'];

          // connect to our Vcenter url
          $vmsdk = new vmware_sdk($vmconnect['server'],$vmconnect['user'],$vmconnect['password']);

          // Gather basic about info.. used for instance ID in URL
          $vmabout = $vmsdk->about();

          $instanceUuid = $vmabout->instanceUuid;

// FIXME.. turn this back on when it works.. tests if we connected or not
/*
          // Print a nice message if we cannot connect
          if (!$vmabout) {
            $htmllines .= <<<EOL
                Unable to connect to VCenter<br>
EOL;
            $response = new xajaxResponse();
            $response->addAssign($form['divname'], "innerHTML", $htmllines);
            return($response->getXML());
          }
*/

/* FIXME LATER
          // Gather a list of hostclusters
          $vmclusters = $vmsdk->get_clusters('junk');
  print_r($vmclusters);
          foreach ($vmclusters->objects as $cluster) {
             $clustername = $cluster->propSet[1]->val;
             foreach ($cluster->propSet[0]->val->ManagedObjectReference as $hostref) {
                 $hostcluster[$hostref] = $clustername;
             }
          }
*/

          // Try to find our vm via FQDN as reported by vmtools
          $vmsearch = $vmsdk->find_vm($form['host_name']);

/* FIXME TEST THIS LATER
          // If we find more than 1 host using the name, lets pick the first
          $multicount = count((array) $vmsearch[returnval]);
          $multimatch = '';
          if ($multicount > 1) {
              $vmsearch = $vmsearch[returnval][0];
              $multimatch = " <span title='Found multiple matching vhosts' style='background-color: yellow;'>Matched: {$multicount}</span>";
          }
*/

          // Get vm details for the vm if we find it by name
          if ($vmsearch->returnval->_) {
            $foundhost=TRUE;
            $vmsummary = $vmsdk->get_path($vmsearch->returnval->_,'VirtualMachine','summary');
            $vmguest = $vmsdk->get_path($vmsearch->returnval->_,'VirtualMachine','guest');
            $vmconfig = $vmsdk->get_path($vmsearch->returnval->_,'VirtualMachine','config');
          } /* else {
            // If we dont find it by name (using vmtools name) then search all names
            $allvms = $vmsdk->get_all_vms();
            foreach ($allvms->objects as $vmlist) {

// TODO: the stuff above seems to work ok.  This part isnot great because get_all_vms is not finding all vms!
// need to fix the traversal spec junk

                // look for the FQDN name of the vm
                if (strtolower($vmlist->propSet[3]->val->name) == strtolower($form['host_name'])) {
                    $foundhost=TRUE;
                    $vmsummary = $vmsdk->get_path($vmlist->obj,'VirtualMachine','summary');
                    $vmguest = $vmsdk->get_path($vmlist->obj,'VirtualMachine','guest');
                    $vmconfig = $vmsdk->get_path($vmlist->obj,'VirtualMachine','config');
                    continue;
                }

                // look for just the host name of the vm it will match the first one it finds
                // so this could be very unreliable. 
                $hname = explode('.',$form['host_name']);
                if (stripos($vmlist->propSet[3]->val->name,$hname[0]) === true) {
                    $foundhost=TRUE;
                    $vmsummary = $vmsdk->get_path($vmsearch->returnval->_,'VirtualMachine','summary');
                    $vmguest = $vmsdk->get_path($vmsearch->returnval->_,'VirtualMachine','guest');
                    $vmconfig = $vmsdk->get_path($vmsearch->returnval->_,'VirtualMachine','config');
                    continue;
                } 
            } 
          } */

          // Should be done gathering data, log out of session
          $vmsdk->logout();

      } // end foreach of vmlogin config variable

    } else {
        $htmllines .= <<<EOL
                No config file found.<br>
EOL;
    }


    // If we never found our host, say so and bail
    if (!$foundhost) {
        $htmllines .= <<<EOL
                Guest not found in VCenter<br>
EOL;
        $response = new xajaxResponse();
        $response->addAssign($form['divname'], "innerHTML", $htmllines);
        return($response->getXML());
    }

/*  TODO:
- Compare mac address to what is in DB and allow a quick edit of some sort
- check the OS types between what is "configured" and what is reported via guesttools.  they "could" be different?
- check the network names.. this could get annoying since not everyone has them named the same?  maybe make it a config flag to check this
- Allow user to power on/off a vm from ONA
*/

    // Convert past the returnval object
    $vmconfig  = $vmconfig->returnval;
    $vmsummary = $vmsummary->returnval;
    $vmguest   = $vmguest->returnval;


    // Gather vm guest details
    $vmid=$vmsummary->propSet->val->vm->_;
    $vmhost=$vmsummary->propSet->val->runtime->host;
    $vmip=$vmsummary->propSet->val->guest->ipAddress;
    foreach ($vmconfig->propSet->val->hardware->device as $vmdev) {
      if ($vmdev->key == '4000') {
        $vmmac = $vmdev->macAddress;
        $vmnetname=$vmdev->deviceInfo->summary;
      }
    }
    $vmos=$vmsummary->propSet->val->config->guestFullName;
    //$vmosguest=$vmsummary->propSet->val->guest->guestFullName;
    $vmmem=$vmsummary->propSet->val->config->memorySizeMB;
    $vmcpu=$vmsummary->propSet->val->config->numCpu;
    $vmdiskuncom=$vmsummary->propSet->val->storage->uncommitted;
    $vmdiskcom=$vmsummary->propSet->val->storage->committed;
    $vmdisk=$vmsummary->propSet->val->storage->uncommitted;
    $vmpower=$vmsummary->propSet->val->runtime->powerState;
    $vmtoolstat=$vmsummary->propSet->val->guest->toolsRunningStatus;
    $vmnotes=$vmsummary->propSet->val->config->annotation;
    $vmnotes_short = truncate($vmnotes, 30);
    $ips = '';
    if (is_array($vmguest->propSet->val->net->ipAddress)) {
      foreach ($vmguest->propSet->val->net->ipAddress as $ipadd) {
        $ips .= $ipadd.'<br>';
      }
    } else {
        $ips .= $vmguest->propSet->val->net->ipAddress.'<br>';
    }

    # figure out the provisioned amount
    $vmdisk =$vmdiskuncom+$vmdiskcom;
    $vmdisk =round($vmdisk/1073741824);
    # then subtract the memory (in gb)
    $vmdisk =$vmdisk - ($vmmem/1024);

    if ($vmpower == 'poweredOn')  { $powerstat = '<img title="Powered On" src="'.$images.'/silk/tick.png" border="0">';}
    if ($vmpower == 'poweredOff') { $powerstat = '<img title="Powered OFF" src="'.$images.'/silk/cross.png" border="0">';}
    if ($vmtoolstat == 'guestToolsRunning')  { $toolstat = '<img title="Running" src="'.$images.'/silk/tick.png" border="0">';}
    if ($vmtoolstat != 'guestToolsRunning')  { $toolstat = '<img title="Not Running: '.$vmtoolstat.'" src="'.$images.'/silk/cross.png" border="0">';}


    $htmllines .= <<<EOL
        <tr onMouseOver="this.className='row-highlight';" onMouseOut="this.className='row-normal';">
            <td class="list-row" style="background-color: {$color};">STATE</td>
            <td class="list-row" style="border-left: 1px solid; border-left-color: #aaaaaa;">Tools:${toolstat} Power:${powerstat}{$multimatch}</td>
            <td class="list-row" style="background-color: {$color};"> </td>
        </tr>
        <tr onMouseOver="this.className='row-highlight';" onMouseOut="this.className='row-normal';">
            <td class="list-row" style="background-color: {$color};">CLUSTER</td>
            <td class="list-row" style="border-left: 1px solid; border-left-color: #aaaaaa;">${hostcluster[${vmhost}]}</td>
        </tr>
        <tr onMouseOver="this.className='row-highlight';" onMouseOut="this.className='row-normal';">
            <td class="list-row" style="background-color: {$color};">OS</td>
            <td class="list-row" style="border-left: 1px solid; border-left-color: #aaaaaa;">${vmos}</td>
        </tr>
        <tr onMouseOver="this.className='row-highlight';" onMouseOut="this.className='row-normal';">
            <td class="list-row" style="background-color: {$color};">MEM</td>
            <td class="list-row" style="border-left: 1px solid; border-left-color: #aaaaaa;">${vmmem}</td>
        </tr>
        <tr onMouseOver="this.className='row-highlight';" onMouseOut="this.className='row-normal';">
            <td class="list-row" style="background-color: {$color};">CPUs</td>
            <td class="list-row" style="border-left: 1px solid; border-left-color: #aaaaaa;">${vmcpu}</td>
        </tr>
        <tr onMouseOver="this.className='row-highlight';" onMouseOut="this.className='row-normal';">
            <td class="list-row" style="background-color: {$color};">DISK</td>
            <td class="list-row" style="border-left: 1px solid; border-left-color: #aaaaaa;">${vmdisk} GB</td>
        </tr>
        <tr onMouseOver="this.className='row-highlight';" onMouseOut="this.className='row-normal';">
            <td class="list-row" style="background-color: {$color};">NET</td>
            <td class="list-row" style="border-left: 1px solid; border-left-color: #aaaaaa;">${vmnetname}</td>
        </tr>
        <tr onMouseOver="this.className='row-highlight';" onMouseOut="this.className='row-normal';">
            <td class="list-row" style="background-color: {$color};">MAC</td>
            <td class="list-row" style="border-left: 1px solid; border-left-color: #aaaaaa;">${vmmac}</td>
        </tr>
        <tr onMouseOver="this.className='row-highlight';" onMouseOut="this.className='row-normal';">
            <td class="list-row" style="background-color: {$color};">IPs</td>
            <td class="list-row" style="border-left: 1px solid; border-left-color: #aaaaaa;">${ips}</td>
        </tr>
        <tr onMouseOver="this.className='row-highlight';" onMouseOut="this.className='row-normal';">
            <td class="list-row" style="background-color: {$color};">NOTES</td>
            <td class="list-row" style="border-left: 1px solid; border-left-color: #aaaaaa;" title="${vmnotes}">${vmnotes_short}</td>
        </tr>
EOL;

    $html .= '<table class="list-box" cellspacing="0" border="0" cellpadding="0">';
    $html .= $htmllines;
    $html .= "</table>";

    // Build a link to this host in vcenter
    $vclink="<a target='_blank' title='View host in vsphere' href='https://${vmserver}:9443/vsphere-client/#extensionId=vsphere.core.vm.summary;context=com.vmware.core.model%3A%3AServerObjectRef~${instanceUuid}%3AVirtualMachine%3A${vmid}~core'><img src='{$images}/silk/application_form_magnify.png' border='0'></a> <a target='_blank' title='Open Console' href='http://${vmserver}:7331/console/?vmId=${vmid}&host={$vmserver}'><img src='{$images}/silk/application_xp_terminal.png' border='0'></a> ";

    // Insert the new table into the window
    $response = new xajaxResponse();
    $response->addAssign($form['divname'], "innerHTML", $html);
    $response->addAssign('vspherelinks', "innerHTML", $vclink);
    $response->addScript($js);
    return($response->getXML());
}







?>
