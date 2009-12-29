<?php
/*
 * Created on Dec 22, 2009
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

require_once 'couchdb/couch.php';
require_once 'couchdb/couchClient.php';
require_once 'couchdb/couchDocument.php';

class Kouchdbmodel_Core
{
	public static $client = NULL;
	private $properties = array();
	
	public static function initialize($config_name)
	{
		if (!isset(self::$client))
		{
			$config = Kohana::config('kouchdb.' . $config_name);
			$url = 'http' . (($config['ssl']) ? 's' : '') . '://' .
					$config['host'] . ':' . $config['port'];
			self::$client = new couchClient($url, $config['database']);
		}
	}
	
	public function docid()
	{
		$docid_split = explode(':', $this->_id);
		return $docid_split[1];
	}
	
	public function __construct($doc_id = NULL, $config_name = 'default')
	{
		$this->doctype = strtolower(str_ireplace('_Model', '', get_class($this)));
		self::initialize($config_name);
		try
		{
			if (isset($doc_id))
			{
				$d_id = "$this->doctype:$doc_id";
				$doc = couchDocument::getInstance(self::$client, $d_id);
			}
		} catch (Exception $ex) {
			
		}
		
		if (!isset($this->_id))
		{
			$this->_id = $this->doctype . ':' . ((int)microtime(true)) . 
				rand(1000, 9999);
		}
	}
	
	public function __set($key, $value)
	{
		$this->properties[$key] = $value;
	}
	
	public function __get($key)
	{
		return $this->properties[$key];
	}
	
	public function save()
	{
		try
		{
			$doc = couchDocument::getInstance(self::$client, $this->_id);
			$doc->set($this->properties);
			self::$client->storeDoc($doc);
		} catch (Exception $ex) {
			$doc = new couchDocument(self::$client);
			$doc->set($this->properties);
		}
		
		
	}
	
	public function __call($method, $parameters)
	{
		if (isset($parameters[0]) && is_array($parameters[0]) && !empty($parameters[0]))
		{
			foreach ($parameters[0] as $param_key => $param_value)
			{
				self::$client->$param_key($param_value);
			}
		}
		$result = self::$client->getView($this->doctype, $method);
		
		return $result;
	}
}
