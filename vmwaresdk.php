<?php

/*

This code was taken from http://don-oles.livejournal.com/289943.html as a starting
point and then modified to do some things I needed.  Thanks for the great example!


*/



class vmware_sdk
{
	private	$client		= null;
	private $serviceContent	= null;


	function __construct($sdk_url,$username,$password) {
		$this->client = new SoapClient(null,
			array (
				'location'	=> $sdk_url,
				'uri'		=>'urn:vim25',
				'exceptions'	=>true
			)
		);
		$this->retrieve_service_content();
		$this->login($username,$password);
	}



	function retrieve_service_content()
	{
		$this->serviceContent = $this->client->__soapCall('RetrieveServiceContent',
			array(
				new SoapVar('<_this xsi:type="xsd:string" type="ServiceInstance">ServiceInstance</_this>',XSD_ANYXML)
			)
		);
	}



	function login($username,$password)
	{
		return $this->client->__soapCall('Login',
			array(
				new SoapVar("<_this xsi:type='xsd:string' type='SessionManager'>".$this->serviceContent->sessionManager."</_this>",XSD_ANYXML),
				new SoapVar ( $username , XSD_STRING, 'xsd:string' , null , 'userName'),
				new SoapVar ( $password , XSD_STRING, 'xsd:string' , null , 'password'),
				null
			)
		);
	}


	function find_vm($fqdn)
	{
		$res = $this->client->__soapCall('FindAllByDnsName',
			array(
				new SoapVar('<_this xsi:type="xsd:string" type="SearchIndex">SearchIndex</_this>',XSD_ANYXML),
				new SoapVar ( $fqdn , XSD_STRING, 'xsd:string' , null , 'dnsName'),
				new SoapVar ( true , XSD_BOOLEAN, 'xsd:boolean' , null , 'vmSearch')
			)
		);
		return $res;
	}

        function get_path($objectid,$type,$path)
        {
                $xml = "
                <specSet>
                        <propSet>
                                <type>$type</type>
                                <pathSet>$path</pathSet>
                                <all>true</all>
                        </propSet>
                        <objectSet>
                                <obj type='$type'>".$objectid."</obj>
                        </objectSet>
                </specSet>";
                $res = $this->client->__soapCall('RetrieveProperties',
                        array(
                                new SoapVar('<_this xsi:type="xsd:string" type="PropertyCollector">'.$this->serviceContent->propertyCollector.'</_this>',XSD_ANYXML),
                                new SoapVar($xml,XSD_ANYXML)
                        )
                );
                return $res;
        }
        
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
                $res = $this->client->__soapCall('RetrieveProperties',
                        array(
                                new SoapVar('<_this xsi:type="xsd:string" type="PropertyCollector">'.$this->serviceContent->propertyCollector.'</_this>',XSD_ANYXML),
                                new SoapVar($xml,XSD_ANYXML)
                        )
                );
                return $res;
        }

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



?>
