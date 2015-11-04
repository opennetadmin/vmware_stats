<?php

/*

This code was taken from http://don-oles.livejournal.com/289943.html as a starting
point and then modified to do some things I needed.  Thanks for the great example!


*/




class vmware_sdk
{
    private	$client		= null;
    private $serviceContent	= null;
    public $vmrootfolder = null;

    // This sets up the initial login session
    function __construct($host,$username,$password) {
    // should the uri -> 'urn:vim25' be set in this? maybe its in wsdl
    // This function can throw a 500 error so 'try' it first and bail.
try {
    $this->client = new SoapClient("https://".$host."/sdk/vimService.wsdl", array("trace" => 1, "location" => "https://".$host."/sdk/", 'cache_wsdl' => WSDL_CACHE_BOTH, "exception" => 0));
    $soapmsg["_this"] = array( "_" => "ServiceInstance", "type" => "ServiceInstance");
    $this->serviceContent = $this->client->RetrieveServiceContent($soapmsg);
} catch (SoapFault $fault) {
    //echo "ERROR => Unable to connect to $host";
return FALSE;
}


    $soapmsg = NULL; // Reset the $soapmsg array..
    $soapmsg["_this"] = $this->serviceContent->returnval->sessionManager;
    $soapmsg["userName"] = $username;
    $soapmsg["password"] = $password;

    $vmrootfolder = $this->serviceContent->returnval->rootFolder->_;

    $this->client->Login($soapmsg);
  }

  // Log out our session
  function logout()
  {
    return $this->client->Logout(array('_this' => $this->serviceContent->returnval->sessionManager));
  }

  // Get the about info
  function about()
  {
    return $this->serviceContent->returnval->about;
  }

  // Find the guest VM using the FQDN
  // Pass in a FQDN to search for
	function find_vm($fqdn)
	{
    $soapmsg = NULL; // Reset the $soapmsg array..
    $soapmsg["_this"] = array( "_" => "SearchIndex", "type" => "SearchIndex");
    $soapmsg["dnsName"] = $fqdn;
    $soapmsg["vmSearch"] = 1;
    return $this->client->FindAllByDnsName($soapmsg);

/*
		$res = $this->client->__soapCall('FindAllByDnsName',
			array(
				new SoapVar('<_this xsi:type="xsd:string" type="SearchIndex">SearchIndex</_this>',XSD_ANYXML),
				new SoapVar ( $fqdn , XSD_STRING, 'xsd:string' , null , 'dnsName'),
				new SoapVar ( true , XSD_BOOLEAN, 'xsd:boolean' , null , 'vmSearch')
			)
		);
		return $res;
*/
	}

  // Get details of a specific guest vm
  function get_vm($objectid)
  {
    $xml = "
          <specSet>
            <propSet>
              <type>VirtualMachine</type>
              <all>true</all>
            </propSet>
            <objectSet>
              <obj type='VirtualMachine'>".$objectid."</obj>
              <selectSet xsi:type='TraversalSpec'>
                <name>traverseChild</name>
                <type>Folder</type>
                <path>childEntity</path>
                <selectSet><name>traverseChild</name></selectSet>
                      <selectSet xsi:type='TraversalSpec'>
                        <type>Datacenter</type>
                        <path>vmFolder</path>
                        <selectSet><name>traverseChild</name></selectSet>
                </selectSet>
              </selectSet>
            </objectSet>
          </specSet>";

/* OLD method
    $xml = "
          <specSet>
            <propSet>
              <type>VirtualMachine</type>
              <pathSet>summary</pathSet>
              <pathSet>config</pathSet>
            </propSet>
            <objectSet>
              <obj type='VirtualMachine'>".$objectid->returnval->_."</obj>
            </objectSet>
         </specSet>";
*/

    $soapmsg = NULL; // Reset the $soapmsg array..
    $soapmsg["_this"] = array( "_" => 'propertyCollector', "type" => "PropertyCollector");
    $soapmsg["specSet"] =  new SoapVar($xml,XSD_ANYXML);

    return $this->client->RetrieveProperties($soapmsg);

  }


  // Generic property selector based on type and path
  function get_path($objectid,$type,$path)
  {
    $xml = "
          <specSet>
            <propSet>
              <type>$type</type>
              <pathSet>$path</pathSet>
            </propSet>
            <objectSet>
              <obj type='$type'>".$objectid."</obj>
            </objectSet>
          </specSet>";
    $soapmsg = NULL; // Reset the $soapmsg array..
    $soapmsg["_this"] = array( "_" => 'propertyCollector', "type" => "PropertyCollector");
    $soapmsg["specSet"] =  new SoapVar($xml,XSD_ANYXML);

    return $this->client->RetrieveProperties($soapmsg);
  }

  function get_clusters($objectid)
  {
// name host network are good pathsets
    $xml = "<specSet>
                  <propSet>
                    <type>ClusterComputeResource</type>
                    <all>true</all>
                  </propSet>
                <objectSet>
                    <obj type='ClusterComputeResource'>".$objectid."</obj>
                </objectSet>
                </specSet>
    ";
    $soapmsg = NULL; // Reset the $soapmsg array..
    $soapmsg["_this"] = array( "_" => 'propertyCollector', "type" => "PropertyCollector");
    $soapmsg["specSet"] =  new SoapVar($xml,XSD_ANYXML);
    // for retreievepropertiesex
    //$soapmsg["options"] =  'RetrieveOptions';

    return $this->client->RetrieveProperties($soapmsg);

/*
                $res = $this->client->__soapCall('RetrievePropertiesEx',
                        array(
                                new SoapVar('<_this xsi:type="xsd:string" type="PropertyCollector">'.$this->serviceContent->propertyCollector.'</_this>',XSD_ANYXML),
                                new SoapVar($xml,XSD_ANYXML),
                                new SoapVar('<options type="RetrieveOptions"></options>',XSD_ANYXML)
                        )
                );
                return $res;
*/
  }
        
  function get_host($objectid)
  {
    $xml = "
          <specSet>
            <propSet>
              <type>HostSystem</type>
              <pathSet>name</pathSet>
              <pathSet>parent</pathSet>
              <pathSet>vm</pathSet>
            </propSet>
            <objectSet>
              <obj type='HostSystem'>".$objectid."</obj>
            </objectSet>
          </specSet>";
    $soapmsg = NULL; // Reset the $soapmsg array..
    $soapmsg["_this"] = array( "_" => 'propertyCollector', "type" => "PropertyCollector");
    $soapmsg["specSet"] =  new SoapVar($xml,XSD_ANYXML);

    return $this->client->RetrieveProperties($soapmsg);
  }
        
  // Get a list of all VMs on the cluster, used when we cant find a
  // specific name.  TODO not yet tested with new style -- so far it does NOT work
  // FIXME: does not actually get all vms.. has a problem with nested traversal
  function get_all_vms()
  {
    $xml = "
          <specSet>
            <propSet>
              <type>VirtualMachine</type>
              <all>true</all>
            </propSet>
            <objectSet>
               <obj type='Folder'>".$this->serviceContent->returnval->rootFolder->_."</obj>
              <selectSet xsi:type='TraversalSpec'>
                <name>traverseChild</name>
                <type>Folder</type>
                <path>childEntity</path>
                <selectSet><name>traverseChild</name></selectSet>
                      <selectSet xsi:type='TraversalSpec'>
                        <type>Datacenter</type>
                        <path>vmFolder</path>
                        <selectSet><name>traverseChild</name></selectSet>
                </selectSet>
              </selectSet>
            </objectSet>
          </specSet>";
    $soapmsg = NULL; // Reset the $soapmsg array..
    $soapmsg["_this"] = array( "_" => 'propertyCollector', "type" => "PropertyCollector");
    $soapmsg["specSet"] =  new SoapVar($xml,XSD_ANYXML);

    return $this->client->RetrieveProperties($soapmsg);
/*
                $res = $this->client->__soapCall('RetrievePropertiesEx',
                        array(
                                new SoapVar('<_this xsi:type="xsd:string" type="PropertyCollector">'.$this->serviceContent->propertyCollector.'</_this>',XSD_ANYXML),
                                new SoapVar($xml,XSD_ANYXML),
                                new SoapVar('<options type="RetrieveOptions"></options>',XSD_ANYXML)
                        )
                );
                return $res;
*/
        }


	static function transform_retrieved_properties($rp)
	{
		$res = array();
		foreach($rp->objects as $obj)
		{
			$oid = $obj->obj;
			$props = array();
			foreach($obj->propSet as $ps)
			{
				$props[$ps->name] = $ps->val;
			}
			$res[$oid] = $props;
		}
		return $res;
	}

}

// ----- OLD CODE STARTS HERE

/*
        function get_clusters()
        {
                $xml = "<specSet>
                        <propSet>
                                <type>ClusterComputeResource</type>
                                <pathSet>host</pathSet>
                                <pathSet>name</pathSet>
                                <all>true</all>
                        </propSet>
                        <objectSet>
                                <obj type='Folder'>".$this->serviceContent->rootFolder."</obj>
                                <selectSet xsi:type='TraversalSpec'>
                                        <name>traverseChild</name>
                                        <type>Folder</type>
                                        <path>childEntity</path>
                                        <selectSet><name>traverseChild</name></selectSet>
                                        <selectSet xsi:type='TraversalSpec'>
                                                <type>Datacenter</type>
                                                <path>hostFolder</path>
                                                <selectSet><name>traverseChild</name></selectSet>
                                        </selectSet>
                                </selectSet>
                        </objectSet>
                </specSet>";
                $res = $this->client->__soapCall('RetrievePropertiesEx',
                        array(
                                new SoapVar('<_this xsi:type="xsd:string" type="PropertyCollector">'.$this->serviceContent->propertyCollector.'</_this>',XSD_ANYXML),
                                new SoapVar($xml,XSD_ANYXML),
                                new SoapVar('<options type="RetrieveOptions"></options>',XSD_ANYXML)
                        )
                );
                return $res;
        }

        // FIXME: does not actually get all vms.. has a problem with nested traversal
        function get_all_vms()
        {
                $xml = "<specSet>
                        <propSet>
                                <type>VirtualMachine</type>
                                <all>true</all>
                        </propSet>
                        <objectSet>
                                <obj type='Folder'>".$this->serviceContent->rootFolder."</obj>
                                <selectSet xsi:type='TraversalSpec'>
                                        <name>traverseChild</name>
                                        <type>Folder</type>
                                        <path>childEntity</path>
                                        <selectSet><name>traverseChild</name></selectSet>
                                        <selectSet xsi:type='TraversalSpec'>
                                                <type>Datacenter</type>
                                                <path>vmFolder</path>
                                                <selectSet><name>traverseChild</name></selectSet>
                                        </selectSet>
                                </selectSet>
                        </objectSet>
                </specSet>";
                $res = $this->client->__soapCall('RetrievePropertiesEx',
                        array(
                                new SoapVar('<_this xsi:type="xsd:string" type="PropertyCollector">'.$this->serviceContent->propertyCollector.'</_this>',XSD_ANYXML),
                                new SoapVar($xml,XSD_ANYXML),
                                new SoapVar('<options type="RetrieveOptions"></options>',XSD_ANYXML)
                        )
                );
                return $res;
        }

	function get_datastores()
	{
		$xml = "<specSet>
			<propSet>
				<type>Datastore</type>
				<all>true</all>
			</propSet>
			<objectSet>
				<obj type='Folder'>".$this->serviceContent->rootFolder."</obj>
				<selectSet xsi:type='TraversalSpec'>
					<name>traverseChild</name>
					<type>Folder</type>
					<path>childEntity</path>
					<selectSet><name>traverseChild</name></selectSet>
					<selectSet xsi:type='TraversalSpec'>
						<type>Datacenter</type>
						<path>datastoreFolder</path>
						<selectSet><name>traverseChild</name></selectSet>
					</selectSet>
				</selectSet>
			</objectSet>
		</specSet>";
		$res = $this->client->__soapCall('RetrievePropertiesEx',
			array(
				new SoapVar('<_this xsi:type="xsd:string" type="PropertyCollector">'.$this->serviceContent->propertyCollector.'</_this>',XSD_ANYXML),
				new SoapVar($xml,XSD_ANYXML),
				new SoapVar('<options type="RetrieveOptions"></options>',XSD_ANYXML)
			)
		);
		return $this->transform_retrieved_properties($res);
	}



}
*/



?>
