<?php

/*

http://www.php.net/manual/en/ref.memcache.php
http://cmunezero.com/2008/08/11/consistent-memcache-hashing-and-failover-with-php/

*/

ini_set('memcache.allow_failover', true); // To transparantly allow failover to other servers
ini_set('memcache.hash_strategy', 'consistent'); // Allow servers to be added or removed from the pool without causing existing keys to be remapped and thus invalidating them.

class XXX_DataGrid_MemCacheD_Extension_MemCache
{	
	protected $settings = array();

	protected $connection;
	protected $compression = false; // false | MEMCACHE_COMPRESSED (ZLIB compression)
	
	public function __construct (array $settings)
	{
		if ($settings['compressed'])
		{
			$this->compression = MEMCACHE_COMPRESSED;
		}
		
		$this->settings = $settings;
		
		$this->connection = new Memcache;
		
		$this->connect($settings);
	}
	
	public function __destruct ()
	{
		$this->disconnect();
	}
	
	public function connect (array $settings)
	{
		if (XXX_Type::isFilledArray($this->settings['servers']))
		{
			foreach ($this->settings['servers'] as $server)
			{
				// There is no connection made at this point, only when a request is done
				$this->connection->addServer($server['address'], $server['port'], $this->settings['persistent'], $server['weight']);
			}
		}
		else
		{
			$this->connection->addServer($settings['address'], $settings['port'], $this->settings['persistent'], $settings['weight']);
		}		
	}
	
	public function disconnect ()
	{
		return $this->connection->close();
	}
		
	public function getStatistics ()
	{
		return $this->connection->getExtendedStats();
	}
	
	public function getKeyPrefix ()
	{
		return $this->settings['keyPrefix'];
	}
	
	public function getSettings ()
	{
		return $this->settings;
	}
	
	public function getConfiguration ()
	{
		$result = array
		(
			'connectionSettings' => $this->settings,
			'hashStrategy' => ini_get('memcache.hash_strategy'),
			'allowFailOver' => ini_get('memcache.allow_failover')
		);
		
		return $result;
	}
		
	// Invalidates all records, it doesn't free the memory, the invalid records can then be overwritten
	public function reset ()
	{
		$result = false;
		
		$flushed = $this->connection->flush();
		
		if ($flushed)
		{		
			// Somehow there's a delay before MemCacheD becomes available again....
			sleep(5);
			
			$result = array
			(
				'success' => true
			);
		}
		
		return $result;
	}
	
	public function getExpirationType ($lifeTimeOrExpirationTimestamp = 0)
	{
		$expirationType = 'forever';
		
		if ($lifeTimeOrExpirationTimestamp == 0)
		{
			$expirationType = 'forever';
		}
		else if ($lifeTimeOrExpirationTimestamp >= 1 && $lifeTimeOrExpirationTimestamp <= 2592000)
		{
			$expirationType = 'lifeTime';
		}
		else if ($lifeTimeOrExpirationTimestamp >= 2592001)
		{
			$expirationType = 'expirationTimestamp';
		}
		
		return $expirationType;
	}
	
	// Records
			
		// Create (Only if it doesn't exist yet)
		
			public function createRecord ($key = '', $value = '', $lifeTimeOrExpirationTimestamp = 0)
			{
				$result = false;
				
				if (XXX_Type::isPositiveInteger($lifeTimeOrExpirationTimestamp))
				{					
					$expirationType = $this->getExpirationType($lifeTimeOrExpirationTimestamp);
					
					if ($key !== '')
					{
						$tempResult = $this->connection->add($key, $value, $this->compression, $lifeTimeOrExpirationTimestamp);
						
						if ($tempResult !== false)
						{
							$result = array
							(
								'success' => true,
								'expirationType' => $expirationType
							);
						}
					}
				}
				
				return $result;
			}
			
			public function createRecords (array $records = array(), $lifeTimeOrExpirationTimestamp = 0)
			{
				$result = false;
				
				if (XXX_Type::isPositiveInteger($lifeTimeOrExpirationTimestamp))
				{
					$expirationType = $this->getExpirationType($lifeTimeOrExpirationTimestamp);
					
					$total = XXX_Array::getFirstLevelItemTotal($records);
					
					if ($total > 0)
					{
						$createdTotal = 0;
						
						foreach ($records as $key => $value)
						{
							$tempResult = $this->connection->add($key, $value, $this->compression, $lifeTimeOrExpirationTimestamp);
							
							if ($tempResult === false)
							{
								break;
							}
							else
							{
								++$createdTotal;
							}
						}
						
						if ($total == $createdTotal)
						{
							$result = array
							(
								'success' => true,
								'expirationType' => $expirationType,
								'total' => $total
							);
						}
					}
				}
				
				return $result;
			}
		
		// Set (Whether it's new or already exists)
			
			public function setRecord ($key = '', $value = '', $lifeTimeOrExpirationTimestamp = 0)
			{
				$result = false;
				
				if (XXX_Type::isPositiveInteger($lifeTimeOrExpirationTimestamp))
				{					
					$expirationType = $this->getExpirationType($lifeTimeOrExpirationTimestamp);
					
					if ($key !== '')
					{
						$tempResult = $this->connection->set($key, $value, $this->compression, $lifeTimeOrExpirationTimestamp);
						
						if ($tempResult !== false)
						{
							$result = array
							(
								'success' => true,
								'expirationType' => $expirationType
							);
						}
					}
				}
				
				return $result;
			}
			
			public function setRecords (array $records = array(), $lifeTimeOrExpirationTimestamp = 0)
			{
				$result = false;
				
				if (XXX_Type::isPositiveInteger($lifeTimeOrExpirationTimestamp))
				{
					$expirationType = $this->getExpirationType($lifeTimeOrExpirationTimestamp);
					
					$total = XXX_Array::getFirstLevelItemTotal($records);
					
					if ($total > 0)
					{
						$setTotal = 0;
						
						foreach ($records as $key => $value)
						{
							$tempResult = $this->connection->set($key, $value, $this->compression, $lifeTimeOrExpirationTimestamp);
							
							if ($tempResult === false)
							{
								break;
							}
							else
							{
								++$setTotal;
							}
						}
						
						if ($total == $setTotal)
						{
							$result = array
							(
								'success' => true,
								'expirationType' => $expirationType,
								'total' => $total
							);
						}
					}
				}
				
				return $result;
			}
		
		// Update (Only if it already exists)
			
			public function updateRecord ($key = '', $value = '', $lifeTimeOrExpirationTimestamp = 0)
			{
				$result = false;
				
				if (XXX_Type::isPositiveInteger($lifeTimeOrExpirationTimestamp))
				{					
					$expirationType = $this->getExpirationType($lifeTimeOrExpirationTimestamp);
					
					if ($key !== '')
					{
						$tempResult = $this->connection->replace($key, $value, $this->compression, $lifeTimeOrExpirationTimestamp);
						
						if ($tempResult !== false)
						{
							$result = array
							(
								'success' => true,
								'expirationType' => $expirationType,
								'affected' => 1
							);
						}
					}
				}
				
				return $result;
			}
			
			public function updateRecords (array $records = array(), $lifeTimeOrExpirationTimestamp = 0)
			{
				$result = false;
				
				if (XXX_Type::isPositiveInteger($lifeTimeOrExpirationTimestamp))
				{
					$expirationType = $this->getExpirationType($lifeTimeOrExpirationTimestamp);
					
					$total = XXX_Array::getFirstLevelItemTotal($records);
					
					if ($total > 0)
					{
						$updatedTotal = 0;
						
						foreach ($records as $key => $value)
						{
							$tempResult = $this->connection->replace($key, $value, $this->compression, $lifeTimeOrExpirationTimestamp);
							
							if ($tempResult === false)
							{
								break;
							}
							else
							{
								++$updatedTotal;
							}
						}
						
						if ($total == $updatedTotal)
						{
							$result = array
							(
								'success' => true,
								'expirationType' => $expirationType,
								'affected' => $total,
								'total' => $total
							);
						}
					}
				}
				
				return $result;
			}
			
		// Counter (Only if it already exists)
		
			public function incrementRecord ($key = '', $step = 1)
			{
				$result = false;
				
				if (XXX_Type::isPositiveInteger($step) && $step > 0)
				{
					if ($key !== '')
					{
						$tempResult = $this->connection->increment($key, $step);
						
						if ($tempResult !== false)
						{
							$result = array
							(
								'success' => true,
								'record' => array
								(
									'key' => $key,
									'value' => $tempResult
								),
								'step' => $step,
								'value' => $tempResult,
								'affected' => 1,
								'total' => 1
							);
						}
					}
				}
				
				return $result;
			}
			
			public function incrementRecords (array $keys = array(), $step = 1)
			{
				$result = false;
				
				if (XXX_Type::isPositiveInteger($step) && $step > 0)
				{
					$total = XXX_Array::getFirstLevelItemTotal($keys);
					
					if ($total > 0)
					{
						$incrementedTotal = 0;
						$records = array();							
						
						foreach ($keys as $key)
						{
							$tempResult = $this->connection->increment($key, $step);
							
							if ($tempResult === false)
							{
								break;
							}
							else
							{
								$records[$key] = $tempResult;
								
								++$incrementedTotal;
							}
						}
						
						if ($total == $incrementedTotal)
						{
							$result = array
							(
								'success' => true,
								'records' => $records,
								'affected' => $total,
								'step' => $step,
								'total' => $total
							);
						}
					}
				}
				
				return $result;
			}
			
			public function decrementRecord ($key = '', $step = 1)
			{
				$result = false;
				
				if (XXX_Type::isPositiveInteger($step) && $step > 0)
				{
					if ($key !== '')
					{
						$tempResult = $this->connection->decrement($key, $step);
						
						if ($tempResult !== false)
						{
							$result = array
							(
								'success' => true,
								'record' => array
								(
									'key' => $key,
									'value' => $tempResult
								),
								'step' => $step,
								'value' => $tempResult,
								'affected' => 1,
								'total' => 1
							);
						}
					}
				}
				
				return $result;
			}
			
			public function decrementRecords (array $keys = array(), $step = 1)
			{
				$result = false;
				
				if (XXX_Type::isPositiveInteger($step) && $step > 0)
				{
					$total = XXX_Array::getFirstLevelItemTotal($keys);
					
					if ($total > 0)
					{
						$decrementedTotal = 0;
						$records = array();							
						
						foreach ($keys as $key)
						{
							$tempResult = $this->connection->decrement($key, $step);
							
							if ($tempResult === false)
							{
								break;
							}
							else
							{
								$records[$key] = $tempResult;
								
								++$decrementedTotal;
							}
						}
						
						if ($total == $decrementedTotal)
						{
							$result = array
							(
								'success' => true,
								'records' => $records,
								'affected' => $total,
								'step' => $step,
								'total' => $total
							);
						}
					}
				}
				
				return $result;
			}
		
		// Retrieve (Only if it already exists)
			
			public function retrieveRecord ($key)
			{
				$result = false;
				
				$exists = false;
				
				// Do multiple retrieves in 1 round trip. Returns an associative array with key => value setup, omits pairs not found.
				$tempResult = $this->connection->get(array($key));
									
				if (XXX_Array::getFirstLevelItemTotal($tempResult) == 1)
				{
					$value = $tempResult[$key];
					
					$result = array
					(
						'success' => true,
						'record' => array
						(
							'key' => $key,
							'value' => $value
						),
						'value' => $value,
						'affected' => 1,
						'total' => 1
					);
				}
								
				return $result;
			}
			
			public function retrieveRecords (array $keys = array())
			{
				$result = false;
				
				$total = XXX_Array::getFirstLevelItemTotal($keys);
				
				if ($total > 0)
				{
					// Do multiple retrieves in 1 round trip. Returns an associative array with key => value setup, omits pairs not found.
					$tempResult = $this->connection->get($keys);
					
					if (XXX_Array::getFirstLevelItemTotal($keys) == XXX_Array::getFirstLevelItemTotal($tempResult))
					{								
						$result = array
						(
							'success' => true,
							'records' => array(),
							'total' => $total
						);
						
						foreach ($keys as $key)
						{
							$result['records'][$key] = $tempResult[$key];
						}
					}
				}
				
				return $result;
			}
		
		// Exist (Only if it already exists)
			
			public function doesRecordExist ($key)
			{
				$result = false;
				
				$exists = false;
				
				// Do multiple retrieves in 1 round trip. Returns an associative array with key => value setup, omits pairs not found.
				$tempResult = $this->connection->get(array($key));
									
				if (XXX_Array::getFirstLevelItemTotal($tempResult) == 1)
				{
					$value = $tempResult[$key];
					
					$result = array
					(
						'success' => true,
						'record' => array
						(
							'key' => $key,
							'value' => $value
						),
						'value' => $value,
						'affected' => 1,
						'total' => 1
					);
				}
								
				return $result;
			}
			
			public function doRecordsExist (array $keys = array())
			{
				$result = false;
				
				$total = XXX_Array::getFirstLevelItemTotal($keys);
				
				if ($total > 0)
				{
					// Do multiple retrieves in 1 round trip. Returns an associative array with key => value setup, omits pairs not found.
					$tempResult = $this->connection->get($keys);
					
					if (XXX_Array::getFirstLevelItemTotal($keys) == XXX_Array::getFirstLevelItemTotal($tempResult))
					{								
						$result = array
						(
							'success' => true,
							'records' => array(),
							'total' => $total
						);
						
						foreach ($keys as $key)
						{
							$result['records'][$key] = XXX_Array::hasKey($tempResult, $key);
						}
					}
				}
				
				return $result;
			}
		
		// Delete
			
			// Pass 0 due to bug http://www.php.net/manual/en/memcache.delete.php#9882
			public function deleteRecord ($key)
			{
				$result = false;
				
				$exists = $this->doesRecordExist($key);
				
				if ($exists['success'])
				{
					$deleted = $this->connection->delete($key, 0);
					
					if ($deleted)
					{
						$result = array
						(
							'success' => true,
							'affected' => 1,
							'total' => 1
						);
					}
				}
				else
				{
					$result = array
					(
						'success' => true,
						'affected' => 0,
						'total' => 1
					);
				}
				
				return $this->connection->delete($key, 0);
			}
			
			public function deleteRecords (array $keys = array())
			{
				$result = false;
				
				$total = XXX_Array::getFirstLevelItemTotal($keys);
				
				if ($total > 0)
				{
					// Do multiple retrieves in 1 round trip. Returns an associative array with key => value setup, omits pairs not found.
					$tempResult = $this->connection->get($keys);
					
					$existingTotal = XXX_Array::getFirstLevelItemTotal($tempResult);
					$deletedTotal = 0;
					
					foreach ($tempResult as $key => $value)
					{
						$deleted = $this->connection->delete($key, 0);
						
						if ($deleted !== false)
						{
							++$deletedTotal;
						}
					}
					
					if ($existingTotal == $deletedTotal)
					{
						$result = array
						(
							'success' => true,
							'affected' => $deletedTotal,
							'total' => $deletedTotal
						);
					}
				}
				
				return $result;
			}
			
	
}

?>