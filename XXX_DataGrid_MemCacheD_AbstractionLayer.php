<?php

class XXX_DataGrid_MemCacheD_AbstractionLayer
{
	protected $connection = false;
	
	public function open ($connection)
	{
		$this->connection = $connection;
		
		return ($this->connection === false ? false : true);
	}
	
	public function close ()
	{
		return ($this->connection !== false) ? $this->connection->disconnect() : false;
	}
	
	// Prefix handling
	
		public function prefixKey ($key = '')
		{
			return $this->connection->getKeyPrefix() . $key;
		}
		
		public function stripPrefixedKey ($prefixedKey = '')
		{
			return XXX_String::getPart($prefixedKey, XXX_String::getCharacterLength($this->connection->getKeyPrefix()));
		}
		
		public function prefixKeys (array $keys = array())
		{
			$keyPrefix = $this->connection->getKeyPrefix();
			
			for ($i = 0, $iEnd = XXX_Array::getFirstLevelItemTotal($keys); $i < $iEnd; ++$i)
			{
				$keys[$i] = $keyPrefix . $keys[$i];
			}
			
			return $keys;
		}
		
		public function stripPrefixedKeys (array $prefixedKeys = array())
		{
			$keyPrefix = $this->connection->getKeyPrefix();
			$keyPrefixCharacterLength = XXX_String::getCharacterLength($keyPrefix);
			
			for ($i = 0, $iEnd = XXX_Array::getFirstLevelItemTotal($prefixedKeys); $i < $iEnd; ++$i)
			{
				$prefixedKeys[$i] = XXX_String::getPart($prefixedKeys[$i], $keyPrefixCharacterLength);
			}
			
			return $prefixedKeys;
		}
		
		public function prefixRecordKeys (array $records = array())
		{
			$keyPrefix = $this->connection->getKeyPrefix();
			
			$prefixedRecords = array();
			
			foreach ($records as $key => $value)
			{
				$prefixedRecords[$keyPrefix . $key] = $value;
			}
			
			return $prefixedRecords;
		}
		
		public function stripPrefixedRecordKeys (array $prefixedRecords = array())
		{
			$keyPrefix = $this->connection->getKeyPrefix();
			$keyPrefixCharacterLength = XXX_String::getCharacterLength($keyPrefix);
			
			$strippedRecords = array();
			
			foreach ($prefixedRecords as $prefixedKey => $value)
			{
				$strippedRecords[XXX_String::getPart($prefixedKey, $keyPrefixCharacterLength)] = $value;
			}
			
			return $strippedRecords;
		}
	
	public function testConnection ()
	{
		$result = true;
		
		$set = $this->createRecord('testConnection', XXX_TimestampHelpers::getCurrentSecondTimestamp());
		
		if ($this->retrieveRecord('testConnection'))
		{
			$result = true;
		}
		
		return $result;
	}
		
	// Records
	
		// Create (Only if it doesn't exist yet)
	
			public function createRecord ($key, $value = '', $lifeTimeOrExpirationTimestamp = 0, $simplifyResult = false)
			{
				$result = false;
				
				if ($this->connection !== false)
				{
					$tempResult = $this->connection->createRecord($this->prefixKey($key), $value, $lifeTimeOrExpirationTimestamp);
					
					if ($tempResult !== false)
					{
						$result = $tempResult;
						
						if ($simplifyResult)
						{
							$result = true;
						}
					}
				}
				
				return $result;
			}
			
			public function createRecords (array $records = array(), $lifeTimeOrExpirationTimestamp = 0, $simplifyResult = false)
			{
				$result = false;
				
				if ($this->connection !== false)
				{
					$tempResult = $this->connection->createRecords($this->prefixRecordKeys($records), $lifeTimeOrExpirationTimestamp);
					
					if ($tempResult !== false)
					{
						$result = $tempResult;
						
						if ($simplifyResult)
						{
							$result = true;
						}
					}
				}
				
				return $result;
			}			
	
		// Set (Whether it's new or already exists)
			
			public function setRecord ($key, $value = '', $lifeTimeOrExpirationTimestamp = 0, $simplifyResult = false)
			{
				$result = false;
				
				if ($this->connection !== false)
				{
					$tempResult = $this->connection->setRecord($this->prefixKey($key), $value, $lifeTimeOrExpirationTimestamp);
					
					if ($tempResult !== false)
					{
						$result = $tempResult;
						
						if ($simplifyResult)
						{
							$result = true;
						}
					}
				}
				
				return $result;
			}
			
			public function setRecords (array $records = array(), $lifeTimeOrExpirationTimestamp = 0, $simplifyResult = false)
			{
				$result = false;
				
				if ($this->connection !== false)
				{
					$tempResult = $this->connection->setRecords($this->prefixRecordKeys($records), $lifeTimeOrExpirationTimestamp);
					
					if ($tempResult !== false)
					{
						$result = $tempResult;
						
						if ($simplifyResult)
						{
							$result = true;
						}
					}
				}
				
				return $result;
			}
						
		// Update (Only if it already exists)
			
			public function updateRecord ($key, $value = '', $lifeTimeOrExpirationTimestamp = 0, $simplifyResult = false)
			{
				$result = false;
				
				if ($this->connection !== false)
				{
					$tempResult = $this->connection->updateRecord($this->prefixKey($key), $value, $lifeTimeOrExpirationTimestamp);
					
					if ($tempResult !== false)
					{
						$result = $tempResult;
						
						if ($simplifyResult)
						{
							$result = $result['affected'];
						}
					}
				}
				
				return $result;
			}
			
			public function updateRecords (array $records = array(), $lifeTimeOrExpirationTimestamp = 0, $simplifyResult = false)
			{
				$result = false;
				
				if ($this->connection !== false)
				{
					$tempResult = $this->connection->updateRecords($this->prefixRecordKeys($records), $lifeTimeOrExpirationTimestamp);
					
					if ($tempResult !== false)
					{
						$result = $tempResult;
						
						if ($simplifyResult)
						{
							$result = $result['affected'];
						}
					}
				}
				
				return $result;
			}
			
		// Counter (Only if it already exists)
			
			public function incrementRecord ($key, $step = 1, $simplifyResult = false)
			{
				$result = false;
				
				if ($this->connection !== false)
				{
					$tempResult = $this->connection->incrementRecord($this->prefixKey($key), $step);
					
					if ($tempResult !== false)
					{
						$result = $tempResult;
						
						$result['record']['key'] = $this->stripPrefixedKey($result['record']['key']);
						
						if ($simplifyResult)
						{
							$result = $result['value'];
						}
					}
				}
				
				return $result;
			}
						
			public function incrementRecords (array $keys = array(), $step = 1, $simplifyResult = false)
			{
				$result = false;
				
				if ($this->connection !== false)
				{
					$tempResult = $this->connection->incrementRecords($this->prefixKeys($keys), $step);
					
					if ($tempResult !== false)
					{
						$result = $tempResult;
						
						$result['records'] = $this->stripPrefixedKeys($result['records']);
						
						if ($simplifyResult)
						{
							$result = $result['records'];
						}
					}
				}
				
				return $result;
			}
			
			public function decrementRecord ($key, $step = 1, $simplifyResult = false)
			{
				$result = false;
				
				if ($this->connection !== false)
				{
					$tempResult = $this->connection->decrementRecord($this->prefixKey($key), $step);
					
					if ($tempResult !== false)
					{
						$result = $tempResult;
						
						$result['record']['key'] = $this->stripPrefixedKey($result['record']['key']);
						
						if ($simplifyResult)
						{
							$result = $result['value'];
						}
					}
				}
				
				return $result;
			}
			
			public function decrementRecords (array $keys = array(), $step = 1, $simplifyResult = false)
			{
				$result = false;
				
				if ($this->connection !== false)
				{
					$tempResult = $this->connection->decrementRecords($this->prefixKeys($keys), $step);
					
					if ($tempResult !== false)
					{
						$result = $tempResult;
						
						$result['records'] = $this->stripPrefixedKeys($result['records']);
						
						if ($simplifyResult)
						{
							$result = $result['records'];
						}
					}
				}
				
				return $result;
			}
					
		// Retrieve (Only if it already exists)
	
			public function retrieveRecord ($key, $simplifyResult = false)
			{
				$result = false;
				
				if ($this->connection !== false)
				{
					$tempResult = $this->connection->retrieveRecord($this->prefixKey($key));
					
					if ($tempResult !== false)
					{
						$result = $tempResult;
						
						$result['record']['key'] = $this->stripPrefixedKey($result['record']['key']);
						
						if ($simplifyResult)
						{
							$result = $result['value'];
						}
					}
				}
				
				return $result;
			}
			
			public function retrieveRecords (array $keys = array(), $simplifyResult = false)
			{
				$result = false;
				
				if ($this->connection !== false)
				{
					$tempResult = $this->connection->retrieveRecords($this->prefixKeys($keys));
					
					if ($tempResult !== false)
					{
						$result = $tempResult;
						
						$result['records'] = $this->stripPrefixedRecordKeys($result['records']);
						
						if ($simplifyResult)
						{
							$result = $result['records'];
						}
					}
				}
				
				return $result;
			}
			
		// Exist
			
			public function doesRecordExist ($key, $simplifyResult = false)
			{
				$result = false;
				
				if ($this->connection !== false)
				{
					$tempResult = $this->connection->doesRecordExist($this->prefixKey($key));
					
					if ($tempResult !== false)
					{
						$result = $tempResult;
						
						$result['record']['key'] = $this->stripPrefixedKey($result['record']['key']);
						
						if ($simplifyResult)
						{
							$result = true;
						}
					}
				}
				
				return $result;
			}
			
			public function doRecordsExist (array $keys = array(), $simplifyResult = false)
			{
				$result = false;
				
				if ($this->connection !== false)
				{
					$tempResult = $this->connection->doRecordsExist($this->prefixKeys($keys));
					
					if ($tempResult !== false)
					{
						$result = $tempResult;
						
						$result['records'] = $this->stripPrefixedRecordKeys($result['records']);
						
						if ($simplifyResult)
						{
							$result = $result['records'];
						}
					}
				}
				
				return $result;
			}
			
		// Delete
				
			public function deleteRecord ($key, $simplifyResult = false)
			{
				$result = false;
				
				if ($this->connection !== false)
				{
					$tempResult = $this->connection->deleteRecord($this->prefixKey($key));
					
					if ($tempResult !== false)
					{
						$result = $tempResult;
						
						if ($simplifyResult)
						{
							$result = $result['affected'];
						}
					}
				}
				
				return $result;
			}
			
			public function deleteRecords (array $keys = array(), $simplifyResult = false)
			{
				$result = false;
				
				if ($this->connection !== false)
				{
					$tempResult = $this->connection->deleteRecords($this->prefixKeys($keys));
					
					if ($tempResult !== false)
					{
						$result = $tempResult;
						
						if ($simplifyResult)
						{
							$result = $result['affected'];
						}
					}
				}
				
				return $result;
			}
}

?>