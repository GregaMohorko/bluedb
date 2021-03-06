<?php

/*
 * BlueDBProperties.php
 * 
 * Copyright 2018 Grega Mohorko
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 14, 2017 Grega Mohorko
 */

namespace BlueDB\Configuration;

use Exception;

class BlueDBProperties
{
	const HOST="host";
	const DB="db";
	const USER="user";
	const PASS="pass";
	const NAMESPACE_ENTITIES="namespace_entities";
	const FORMAT_DATE="format_date";
	const FORMAT_TIME="format_time";
	const FORMAT_DATETIME="format_datetime";
	const INCLUDE_MANYTOONE="includeManyToOne";
	const INCLUDE_ONETOMANY="includeOneToMany";
	const INCLUDE_MANYTOMANY="includeManyToMany";
	
	/**
	 * @var BlueDBProperties
	 */
	private static $instance;
	
	/**
	 * @return BlueDBProperties
	 */
	public static function instance()
	{
		if(self::$instance===null){
			throw new Exception("The BlueDBProperties instance was not initialized.");
		}
		return self::$instance;
	}
	
	/**
	 * @param array $config
	 */
	public static function init($config)
	{
		self::$instance=new BlueDBProperties($config);
	}
	
	/**
	 * @var string
	 */
	public $MySQL_host;
	/**
	 * @var string
	 */
	public $MySQL_databaseName;
	/**
	 * @var string
	 */
	public $MySQL_username;
	/**
	 * @var string
	 */
	public $MySQL_password;
	
	/**
	 * @var string
	 */
	public $Namespace_Entities="";
	
	/**
	 * @var string
	 */
	public $Format_Date="Y-m-d";
	/**
	 * @var string
	 */
	public $Format_Time="H:i:s";
	/**
	 * @var string
	 */
	public $Format_DateTime="Y-m-d H:i:s";
	
	/**
	 * @var bool
	 */
	public $includeManyToOne=true;
	/**
	 * @var bool
	 */
	public $includeOneToMany=true;
	/**
	 * @var bool
	 */
	public $includeManyToMany=true;
	
	/**
	 * @param array $config
	 */
	private function __construct($config)
	{
		$mandatoryValues=[self::HOST,self::DB,self::USER,self::PASS];
		foreach($mandatoryValues as $mandatoryValue){
			if(!array_key_exists($mandatoryValue, $config)){
				throw new Exception("The configuration file has to specify a '$mandatoryValue' value.");
			}
		}
		
		$this->MySQL_host=$config[self::HOST];
		$this->MySQL_databaseName=$config[self::DB];
		$this->MySQL_username=$config[self::USER];
		$this->MySQL_password=$config[self::PASS];
		
		if(array_key_exists(self::NAMESPACE_ENTITIES, $config)){
			$this->Namespace_Entities=$config[self::NAMESPACE_ENTITIES];
		}
		if(array_key_exists(self::FORMAT_DATE, $config)){
			$this->Format_Date=$config[self::FORMAT_DATE];
		}
		if(array_key_exists(self::FORMAT_TIME, $config)){
			$this->Format_Time=$config[self::FORMAT_TIME];
		}
		if(array_key_exists(self::FORMAT_DATETIME, $config)){
			$this->Format_DateTime=$config[self::FORMAT_DATETIME];
		}
		
		if(array_key_exists(self::INCLUDE_MANYTOONE, $config)){
			$this->includeManyToOne=boolval($config[self::INCLUDE_MANYTOONE]);
		}
		if(array_key_exists(self::INCLUDE_ONETOMANY, $config)){
			$this->includeOneToMany=boolval($config[self::INCLUDE_ONETOMANY]);
		}
		if(array_key_exists(self::INCLUDE_MANYTOMANY, $config)){
			$this->includeManyToMany=boolval($config[self::INCLUDE_MANYTOMANY]);
		}
	}
	
	private function __clone() { }
	private function __wakeup() { }
}
