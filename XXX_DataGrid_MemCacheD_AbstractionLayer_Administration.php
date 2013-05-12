<?php

class XXX_DataGrid_MemCacheD_AbstractionLayer_Administration extends XXX_DataGrid_MemCacheD_AbstractionLayer
{
	public $recordPrefix = 'Administration_';
	
	public function getStatistics ()
	{
		return ($this->connection !== false) ? $this->connection->getStatistics() : false;
	}
	
	public function getConfiguration ()
	{
		return ($this->connection !== false) ? $this->connection->getConfiguration() : false;
	}

	public function reset ()
	{
		return ($this->connection !== false) ? $this->connection->reset() : false;
	}
}

?>