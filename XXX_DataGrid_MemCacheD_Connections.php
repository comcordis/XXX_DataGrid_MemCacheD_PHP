<?php

abstract class XXX_DataGrid_MemCacheD_Connections
{
	public static $defaultPrefix = 'XXX';
	
	public static $dataGrids = array();	
	public static $connections = array();
	public static $abstractionLayers = array();
	
	public static $validConnectionTypes = array
	(
		'content',
		'administration'
	);
	
	public static function setDefaultPrefix ($defaultPrefix = '')
	{
		self::$defaultPrefix = $defaultPrefix;
	}
	
	public static function add ($prefix = '', $name = '', $settings = array(), $deployEnvironment = false, $recycleName = false)
	{
		if ($prefix == '')
		{
			$prefix = self::$defaultPrefix;
		}
		
		$deployEnvironment = XXX::normalizeDeployEnvironment($deployEnvironment);
		
		if ($settings['connectionType'] == '')
		{
			$settings['connectionType'] = 'content';
		}
		
		if (!XXX_Array::hasValue(self::$validConnectionTypes, $settings['connectionType']))
		{
			$settings['connectionType'] = 'content';
		}
		
		if ($settings['port'] == '')
		{
			$settings['port'] = 11211;
		}
		
		if ($settings['address'] == '')
		{
			$settings['address'] = '127.0.0.1';
		}
		
		if (!XXX_Type::isBoolean($settings['persistent']))
		{
			$settings['persistent'] = false;
		}
		
		$dataGrid = $prefix . '_';
		$dataGrid .= $deployEnvironment . '_';
		$dataGrid .= $name;
		
		self::$dataGrids[$name] = $dataGrid;
		
		$settings['keyPrefix'] = $dataGrid . '_' . $settings['keyPrefix'];
		
		if ($recycleName !== false && array_key_exists($recycleName, self::$dataGrids))
		{
			self::$connections[$name] =& self::$connections[$recycleName];
			
			self::$abstractionLayers[$name] =& self::$abstractionLayers[$recycleName];
		}
		else
		{
			self::$connections[$name] = new XXX_DataGrid_MemCacheD_Extension_MemCache($settings);
			
			self::$abstractionLayers[$name] = new XXX_DataGrid_MemCacheD_AbstractionLayer_Administration();
			self::$abstractionLayers[$name]->open(self::$connections[$name]);
		}
	}
	
	public static function initialize ()
	{
		$settings = array
		(
			'connectionType' => 'administration'
		);
		
		self::add('XXX', 'development', $settings);
		self::add('XXX', 'local', $settings, false, 'development');
		
		self::setDefaultPrefix(XXX::$deploymentInformation['project']);
	}
}

?>