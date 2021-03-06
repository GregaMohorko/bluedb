<?php

/*
 * PropertyTypeSanitizer.php
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
 * @copyright May 23, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

use BlueDB\Utility\StringUtility;
use DateTime;
use Exception;

/**
 * Sanitizes raw string values of properties received from the client and creates actual objects.
 * 
 * For example, if 'john.shepardgmail.com' is provided for an Email property, it throws an exception because the value is not a valid email.
 */
abstract class PropertySanitizer
{
	/**
	 * @param string $value
	 * @param PropertyTypeEnum $type
	 * return mixed
	 */
	public static function sanitize($value,$type)
	{
		if(!is_string($value)){
			// no need to sanitize non-string values ...
			return PropertyCreator::create($value, $type);
		}
		
		// everything is automatically escaped when using prepared statements
		// if not using prepared statements, then the user should escape himself
		//$escapedValue=MySQL::escapeString($value);
		
		switch($type){
			case PropertyTypeEnum::TEXT:
				return $value;
			case PropertyTypeEnum::INT:
				return self::sanitizeInt($value);
			case PropertyTypeEnum::FLOAT:
				return self::sanitizeFloat($value);
			case PropertyTypeEnum::ENUM:
				return self::sanitizeEnum($value);
			case PropertyTypeEnum::BOOL:
				return self::sanitizeBool($value);
			case PropertyTypeEnum::DATE:
				return self::sanitizeDate($value);
			case PropertyTypeEnum::TIME:
				return self::sanitizeTime($value);
			case PropertyTypeEnum::DATETIME:
				return self::sanitizeDatetime($value);
			case PropertyTypeEnum::EMAIL:
				return self::sanitizeEmail($value);
			case PropertyTypeEnum::COLOR:
				return self::sanitizeColor($value);
			default:
				throw new Exception("The provided PropertyTypeEnum '".$type."' is not supported.");
		}
	}
	
	/**
	 * @param string $value
	 * @return int
	 */
	private static function sanitizeInt($value)
	{
		$filteredValue=filter_var($value, FILTER_VALIDATE_INT);
		if($filteredValue===false){
			throw new Exception("Int filter failed for '$value'.");
		}
		return PropertyCreator::createInt($filteredValue);
	}
	
	/**
	 * @param string $value
	 * @return float
	 */
	private static function sanitizeFloat($value)
	{
		$valueWithoutCommas=str_replace(",", ".", $value);
		$filteredValue=filter_var($valueWithoutCommas, FILTER_VALIDATE_FLOAT);
		if($filteredValue===false){
			throw new Exception("Float filter failed for '$value'.");
		}
		return PropertyCreator::createFloat($filteredValue);
	}
	
	/**
	 * @param string $value
	 * @return int
	 */
	private static function sanitizeEnum($value)
	{
		$filteredValue=filter_var($value,FILTER_VALIDATE_INT);
		if($filteredValue===false){
			throw new Exception("Int filter failed for '$value'.");
		}
		return PropertyCreator::createEnum($filteredValue);
	}
	
	private static $boolFilterOptions=array("options" => array("min_range"=>0,"max_range"=>1));
	
	/**
	 * @param string $value
	 * @return bool
	 */
	private static function sanitizeBool($value)
	{
		$filteredValue=filter_var($value, FILTER_VALIDATE_INT,self::$boolFilterOptions);
		if($filteredValue===false){
			throw new Exception("Bool filter failed for '$value'.");
		}
		return PropertyCreator::createBool($filteredValue);
	}
	
	/**
	 * @param string $value
	 * @return DateTime
	 */
	private static function sanitizeDate($value)
	{
		$shortenedValue=substr($value, 0, 10);
		return PropertyCreator::createDate($shortenedValue);
	}
	
	/**
	 * @param string $value
	 * @return DateTime
	 */
	private static function sanitizeTime($value)
	{
		return PropertyCreator::createTime($value);
	}
	
	/**
	 * @param string $value
	 * @return DateTime
	 */
	private static function sanitizeDatetime($value)
	{
		return PropertyCreator::createDateTime($value);
	}
	
	/**
	 * @param string $value
	 * @return string
	 */
	private static function sanitizeEmail($value)
	{
		if(strlen($value)==0){
			return $value;
		}
		$normalEscapedValue= StringUtility::replaceSlavicCharsToNormalEquivalents($value);
		$emailValue=filter_var($normalEscapedValue, FILTER_VALIDATE_EMAIL);
		if($emailValue===false){
			throw new Exception("Email filter failed for '$value'.");
		}
		return $emailValue;
	}
	
	/**
	 * @param string $value
	 * @return string
	 */
	private static function sanitizeColor($value)
	{
		if(strlen($value)!=6){
			throw new Exception("Value '$value' is not a valid color.");
		}
		
		$upperValue=strtoupper($value);
		
		for($i=5;$i>=0;--$i){
			switch($upperValue[$i]){
				case '0':
				case '1':
				case '2':
				case '3':
				case '4':
				case '5':
				case '6':
				case '7':
				case '8':
				case '9':
				case 'A':
				case 'B':
				case 'C':
				case 'D':
				case 'E':
				case 'F':
					break;
				default:
					throw new Exception("Value '$value' is not a valid color.");
			}
		}
		
		return $upperValue;
	}
}
