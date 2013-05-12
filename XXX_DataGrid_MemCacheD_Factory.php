<?php

abstract class XXX_DataGrid_MemCacheD_Factory
{	
	public static $connections = array();
	
	public static function getHostsWithServer ($hostSettingsOrKeyPath = '', $server_ID = '')
	{
		global $XXX_Resources_DataGrid_MemCacheD_Hosts;
		
		if ($server_ID == '')
		{
			$server_ID = XXX_Server::$server_ID;
		}
		
		if ($hostSettingsOrKeyPath == '')
		{
			$hostSettingsOrKeyPath = $XXX_Resources_DataGrid_MemCacheD_Hosts;
		}
		
		if (XXX_Type::isArray($hostSettingsOrKeyPath))
		{
			$hostSettings = $hostSettingsOrKeyPath;
		}
		else
		{
			$hostSettings = XXX_Array::traverseKeyPath($XXX_Resources_DataGrid_MemCacheD_Hosts, $hostSettingsOrKeyPath);
		}
		
		return self::getHostsWithServerSub('', $hostSettings, $server_ID);
	}
	
		public static function getHostsWithServerSub ($keyPath = '', $hostSettings = array(), $server_ID = '')
		{
			$result = false;
			
			$hostsWithServer = array();
			
			if ($hostSettings['server_ID'] || $hostSettings['servers'])
			{
				$hostSettings = self::processHostSettings($hostSettings);
				
				foreach ($hostSettings['servers'] as $serverSettings)
				{
					if ($serverSettings['server_ID'] == $server_ID)
					{
						$hostsWithServer[] = $keyPath;
						break;
					}
				}
			}
			else
			{
				foreach ($hostSettings as $keyPathPart => $hostSettingsSub)
				{
					$tempKeyPath = $keyPath;
					
					if ($tempKeyPath != '')
					{
						$tempKeyPath .= '>';
					}
					
					$tempKeyPath .= $keyPathPart;
				
					$tempResult = self::getHostsWithServerSub($tempKeyPath, $hostSettingsSub, $server_ID);
					
					if ($tempResult)
					{
						foreach ($tempResult as $tempResultSub)
						{
							$hostsWithServer[] = $tempResultSub;
						}
					}
				}
			}
			
			if (XXX_Array::getFirstLevelItemTotal($hostsWithServer))
			{
				$result = $hostsWithServer;
			}
			
			return $result;
		}
		
	public static function doesHostHaveServer ($hostSettingsOrKeyPath = '', $server_ID = '')
	{
		return self::getHostsWithServer($hostSettingsOrKeyPath, $server_ID) !== false;
	}
	
	public static function create ($connectionIdentifier, $hostSettingsOrKeyPath, $connectionType = 'content')
	{
		global $XXX_Resources_DataGrid_MemCacheD_Hosts;
		
		$result = false;
		
		if (XXX_Type::isArray($hostSettingsOrKeyPath))
		{
			$hostSettings = $hostSettingsOrKeyPath;
			$keyPath = '';
		}
		else
		{
			$hostSettings = XXX_Array::traverseKeyPath($XXX_Resources_DataGrid_MemCacheD_Hosts, $hostSettingsOrKeyPath);
			$keyPath = $hostSettingsOrKeyPath;
		}
		
		
		if ($connectionType == 'content' || $connectionType == 'administration')
		{
			$connectionIdentifier = $connectionIdentifier . '_' . $connectionType;
			
			$result = self::testForExistingConnection($connectionIdentifier);
			
			if (!$result)
			{
				$hostSettings['connectionIdentifier'] = $connectionIdentifier;
				$hostSettings['connectionType'] = $connectionType;
				$hostSettings['keyPath'] = $keyPath;
				
				$hostSettings = self::processHostSettings($hostSettings);
				
				if (XXX_Array::getFirstLevelItemTotal($hostSettings['servers']) > 0)
				{
					$result = self::createNewConnection($hostSettings);
				}
			}
		}
		
		return $result;
	}
	
	public static function testForExistingConnection ($connectionIdentifier = '')
	{
		$result = false;
		
		// Check if a connection already exists
		foreach (self::$connections as $key => $connection)
		{
			if ($connectionIdentifier == $key)
			{
				$result = $connection;
				break;
			}
		}
		
		return $result;
	}
	
	public static function createNewConnection (array $hostSettings)
	{
		$result = false;
		
		$connection = self::createExtensionConnection($hostSettings);
				
		if ($connection !== false)
		{
			self::$connections[$hostSettings['connectionIdentifier']] = $connection;
			
			$result = $connection;
		}
		
		return $result;
	}
	
	public static function processHostSettings (array $hostSettings = array())
	{
		// If just 1 server, reformat it.
		if (!$hostSettings['servers'] || (XXX_Array::getFirstLevelItemTotal($hostSettings['servers']) == 0 && XXX_Type::isValue($hostSettings['server_ID'])))
		{
			$hostSettings['servers'] = array($hostSettings);
		}
		
		if (!($hostSettings['extension'] == 'MemCacheD' || $hostSettings['extension'] == 'MemCache'))
		{
			$hostSettings['extension'] = 'MemCache';
		}
		
		if (!XXX_Type::isPositiveInteger($hostSettings['port']))
		{
			$hostSettings['port'] = 11211;
		}
		
		if (!XXX_Type::isPositiveInteger($hostSettings['weight']))
		{
			$hostSettings['weight'] = 1;
		}
		
		if (!XXX_Type::isBoolean($hostSettings['persistent']))
		{
			$hostSettings['persistent'] = false;
		}
				
		for ($i = 0, $iEnd = XXX_Array::getFirstLevelItemTotal($hostSettings['servers']); $i < $iEnd; ++$i)
		{
			$hostSettings['servers'][$i] = self::processServerSettings($hostSettings['servers'][$i], $hostSettings);
		}
		
		return $hostSettings;
	}
	
	public static function processServerSettings (array $serverSettings = array(), array $hostSettings = array())
	{
		global $XXX_Resources_Servers;
		
		$serverSettings['connectionIdentifier'] = $hostSettings['connectionIdentifier'];
		$serverSettings['connectionType'] = $hostSettings['connectionType'];
		$serverSettings['keyPath'] = $hostSettings['keyPath'];
		
		if (!XXX_Type::isValue($serverSettings['address']))
		{
			$tempServer = XXX_Server::getServer($serverSettings['server_ID']);
				
			if (XXX_Server::isCurrentServer($serverSettings['server_ID']))
			{			
				$serverSettings['address'] = $tempServer['address']['ipv4']['local']['ip'];
			}
			else
			{
				$currentServer = XXX_Server::getCurrentServer();
				
				// Same VLAN
				if ($currentServer['address']['ipv4']['private']['vlan'] == $tempServer['address']['ipv4']['private']['vlan'])
				{
					$serverSettings['address'] = $tempServer['address']['ipv4']['private']['ip'];
				}
				else
				{
					$serverSettings['address'] = $tempServer['address']['ipv4']['public']['ip'];
				}
			}
		}
		
		if (!XXX_Type::isValue($serverSettings['port']))
		{
			if (XXX_Type::isValue($hostSettings['port']))
			{
				$serverSettings['port'] = $hostSettings['port'];
			}
		}
		
		$serverSettings['port'] = XXX_Default::toPositiveInteger($serverSettings['port'], 11211);
				
		if (!XXX_Type::isValue($serverSettings['weight']))
		{
			if (XXX_Type::isValue($hostSettings['weight']))
			{
				$serverSettings['weight'] = $hostSettings['weight'];
			}
		}
		
		$serverSettings['weight'] = XXX_Default::toPositiveInteger($serverSettings['weight'], 1);
				
		if (!XXX_Type::isValue($serverSettings['keyPrefix']))
		{
			if (XXX_Type::isValue($hostSettings['keyPrefix']))
			{
				$serverSettings['keyPrefix'] = $hostSettings['keyPrefix'];
			}
			else
			{
				$serverSettings['keyPrefix'] = '';
			}
		}
				
		if (!XXX_Type::isBoolean($serverSettings['persistent']))
		{
			$serverSettings['persistent'] = $hostSettings['persistent'];
		}
		
		$serverSettings['persistent'] = XXX_Default::toBoolean($serverSettings['persistent'], false);
		
		return $serverSettings;
	}
	
	
	public static function createExtensionConnection (array $hostSettings)
	{
		$result = false;
		
		switch ($hostSettings['extension'])
		{
			case 'MemCache':
			default:
				if (XXX_PHP::hasExtension('memcache'))
				{
					$result = new XXX_DataGrid_MemCacheD_Extension_MemCache($hostSettings);
				}
				break;
		}
		
		return $result;
	}
}

?>