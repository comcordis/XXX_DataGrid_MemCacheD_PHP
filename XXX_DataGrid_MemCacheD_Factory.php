<?php

abstract class XXX_DataGrid_MemCacheD_Factory
{	
	public static $connections = array();
		
	public static function create ($connectionIdentifier, $hostSettingsOrKeyPath, $connectionType = 'content')
	{
		$result = false;
		
		if (XXX_Type::isArray($hostSettingsOrKeyPath))
		{
			$hostSettings = $hostSettingsOrKeyPath;
			$keyPath = '';
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
		if (!$hostSettings['servers'] || (XXX_Array::getFirstLevelItemTotal($hostSettings['servers']) == 0))
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
		$serverSettings['connectionIdentifier'] = $hostSettings['connectionIdentifier'];
		$serverSettings['connectionType'] = $hostSettings['connectionType'];
		$serverSettings['keyPath'] = $hostSettings['keyPath'];
				
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