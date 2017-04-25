<?php

/*
 * Expression.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 14, 2017 Grega Mohorko
 */

namespace BlueDB\DataAccess\Criteria;

use Exception;
use DateTime;
use BlueDB\DataAccess\JoinType;
use BlueDB\DataAccess\Joiner;
use BlueDB\Entity\FieldTypeEnum;
use BlueDB\Entity\PropertyTypeEnum;
use BlueDB\Entity\SubEntity;
use BlueDB\Entity\StrongEntity;
use BlueDB\Entity\FieldEntity;
use BlueDB\Utility\ArrayUtility;

class Expression
{
	/**
	 * @var string
	 */
	public $EntityClass;
	
	/**
	 * @var array 4D: JoiningEntityClass -> JoinType -> JoinBasePlace -> JoinBaseColumn -> JoinColumn = JoinName
	 */
	public $Joins;
	
	/**
	 * @var string
	 */
	public $Term;
	
	/**
	 * @var array
	 */
	public $Values;
	
	/**
	 * @var array This is used for parameter binding in Prepared Statements.
	 */
	public $ValueTypes;
	
	/**
	 * @var int
	 */
	public $ValueCount;
	
	/**
	 * @param string $entityClass
	 * @param array $joins
	 * @param string $term
	 * @param array $values [optional]
	 * @param array $valueTypes [optional]
	 */
	private function __construct($entityClass,$joins,$term,$values=null,$valueTypes=null)
	{
		$this->EntityClass=$entityClass;
		if($joins!=null)
			$this->Joins=$joins;
		else
			$this->Joins=[];
		$this->Term=$term;
		if($values==null || $valueTypes==null){
			$this->Values=[];
			$this->ValueTypes=[];
			$this->ValueCount=0;
		}else{
			$this->Values=$values;
			$this->ValueTypes=$valueTypes;
			$this->ValueCount=count($this->Values);
		}
	}
	
	/**
	 * Selects only those entries whose field value is above the provided one. Can be used for int, float, date, time and datetime.
	 * 
	 * @param string $criteriaClass Class of the base entity, on which the criteria will be put.
	 * @param string $field Field (of the restriction object), on which the restriction shall take place.
	 * @param mixed $value Inclusive bottom value.
	 * @param string $parentClass [optional] Actual parent class (if criteria class is SubEntity) that contains the specified field.
	 * @return Expression
	 */
	public static function above($criteriaClass,$field,$value,$parentClass=null)
	{
		return self::abovePrivate($criteriaClass, $field, $value, true, $parentClass);
	}
	
	/**
	 * @param string $criteriaClass
	 * @param string $field
	 * @param mixed $value
	 * @param bool $hasToPrepareStatement
	 * @param string $parentClass
	 * @return Expression
	 */
	private static function abovePrivate($criteriaClass,$field,$value,$hasToPrepareStatement,$parentClass)
	{
		if($parentClass===null)
			$parentClass=$criteriaClass;
		
		$joiningFieldBaseConstName=$parentClass."::".$field;
		$fieldType=constant($joiningFieldBaseConstName."FieldType");
		if($fieldType!=FieldTypeEnum::PROPERTY)
			throw new Exception("Only PROPERTY field types are allowed for after expressions.");
		
		$propertyType=constant($joiningFieldBaseConstName."PropertyType");
		switch($propertyType){
			case PropertyTypeEnum::INT:
			case PropertyTypeEnum::FLOAT:
			case PropertyTypeEnum::DATETIME:
			case PropertyTypeEnum::DATE:
			case PropertyTypeEnum::TIME:
				break;
			default:
				throw new Exception("Property type '".$propertyType."' is not supported for after expression.");
		}
		
		$valueS=PropertyTypeEnum::convertToString($value, $propertyType);
		
		if($criteriaClass==$parentClass){
			$termName=$parentClass::getTableName();
			$theJoin=null;
		}else{
			$joinBasePlace=$criteriaClass::getTableName();
			$joinBaseColumn=$criteriaClass::getIDColumn();
			$joinColumn=$parentClass::getIDColumn();
			
			$joinName=Joiner::getJoinName($parentClass, JoinType::INNER, $joinBasePlace, $joinBaseColumn, $joinColumn);
			$termName=$joinName;
			$theJoin=Joiner::createJoin($parentClass,$joinBasePlace, $joinBaseColumn, $joinColumn, $joinName);
		}
		
		$term=$termName.".".$field." > ";
		
		if($hasToPrepareStatement){
			$term.="?";
			
			$valueType=PropertyTypeEnum::getPreparedStmtType($propertyType);
			
			$values=[$valueS];
			$valueTypes=[$valueType];
		}else{
			$term.="'".$valueS."'";
			$values=[];
			$valueTypes=[];
		}
		
		return new Expression($criteriaClass, $theJoin, $term, $values, $valueTypes);
	}
	
	/**
	 * Selects only those entries whose DateTime field value is after the current date & time.
	 * 
	 * @param string $criteriaClass Class of the base entity, on which the criteria will be put.
	 * @param string $field Field (of the restriction object), on which the restriction shall take place.
	 * @param string $parentClass [optional] Actual parent class (if criteria class is SubEntity) that contains the specified field.
	 * @return Expression
	 */
	public static function afterNow($criteriaClass,$field,$parentClass=null)
	{
		$dateTimeValue=new DateTime();
		return self::abovePrivate($criteriaClass,$field,$dateTimeValue,false,$parentClass);
	}
	
	/**
	 * Selects only those entries whose DateTime field value is after the provided DateTime value.
	 * 
	 * @param string $criteriaClass Class of the base entity, on which the criteria will be put.
	 * @param string $field Field (of the restriction object), on which the restriction shall take place.
	 * @param type $dateTimeValue
	 * @param string $parentClass [optional] Actual parent class (if criteria class is SubEntity) that contains the specified field.
	 * @return Expression
	 */
	public static function after($criteriaClass,$field,$dateTimeValue,$parentClass=null)
	{
		return self::abovePrivate($criteriaClass, $field, $dateTimeValue, true,$parentClass);
	}
	
	/**
	 * Used for int,float,date,time and datetime properties.
	 * 
	 * @param string $criteriaClass Class of the base entity, on which the criteria will be put.
	 * @param string $field Field (of the restriction object), on which the restriction shall take place.
	 * @param mixed $min Inclusive min value.
	 * @param mixed $max Inclusive max value.
	 * @param string $parentClass [optional] Actual parent class (if criteria class is SubEntity) that contains the specified field.
	 * @return Expression
	 */
	public static function between($criteriaClass,$field,$min,$max,$parentClass=null)
	{
		if($parentClass===null)
			$parentClass=$criteriaClass;
		
		$joiningFieldBaseConstName=$parentClass."::".$field;
		$fieldType=constant($joiningFieldBaseConstName."FieldType");
		if($fieldType!=FieldTypeEnum::PROPERTY)
			throw new Exception("Only PROPERTY field types are allowed for between expressions. '".$fieldType."' was provided.");
		
		$propertyType=constant($joiningFieldBaseConstName."PropertyType");
		switch($propertyType){
			case PropertyTypeEnum::INT:
			case PropertyTypeEnum::FLOAT:
			case PropertyTypeEnum::DATETIME:
			case PropertyTypeEnum::DATE:
			case PropertyTypeEnum::TIME:
				break;
			default:
				throw new Exception("Property type '".$propertyType."' is not supported for between expression.");
		}
		
		$minS=PropertyTypeEnum::convertToString($min, $propertyType);
		$maxS=PropertyTypeEnum::convertToString($max, $propertyType);
		
		if($criteriaClass==$parentClass){
			$termName=$parentClass::getTableName();
			$theJoin=null;
		}else{
			$joinBasePlace=$criteriaClass::getTableName();
			$joinBaseColumn=$criteriaClass::getIDColumn();
			$joinColumn=$parentClass::getIDColumn();
			
			$joinName=Joiner::getJoinName($parentClass, JoinType::INNER, $joinBasePlace, $joinBaseColumn, $joinColumn);
			$termName=$joinName;
			$theJoin=Joiner::createJoin($parentClass,$joinBasePlace, $joinBaseColumn, $joinColumn, $joinName);
		}
		
		$term=$termName.".".$field." BETWEEN ? AND ?";
		
		$valueType=PropertyTypeEnum::getPreparedStmtType($propertyType);
		
		$values=[$minS,$maxS];
		$valueTypes=[$valueType,$valueType];
		
		return new Expression($criteriaClass, $theJoin, $term, $values, $valueTypes);
	}
	
	/**
	 * Only allowed for text and email properties.
	 * 
	 * @param string $criteriaClass Class of the base entity, on which the criteria will be put.
	 * @param string $field A text property field (of the restriction object), on which the restriction shall take place.
	 * @param string $value Must be a string of length > 0.
	 * @param string $parentClass [optional] Actual parent class (if criteria class is SubEntity) that contains the specified field.
	 * @return Expression
	 */
	public static function contains($criteriaClass,$field,$value,$parentClass=null)
	{
		if($parentClass==null)
			$parentClass=$criteriaClass;
		$joiningFieldBaseConstName=$parentClass."::".$field;
		
		if($value===null)
			throw new Exception("Null value is not allowed for contains expression.");
		if(!is_string($value))
			throw new Exception("Value for contains expression must be a string.");
		
		/*@var $type FieldTypeEnum*/
		$type=constant($joiningFieldBaseConstName."FieldType");
		if($type!=FieldTypeEnum::PROPERTY)
			throw new Exception("Only property fields are allowed in contains expression. The provided field was of type '".$type."'.");
		
		/* @var $propertyType PropertyTypeEnum */
		$propertyType=constant($joiningFieldBaseConstName."PropertyType");
		switch($propertyType){
			case PropertyTypeEnum::TEXT:
			case PropertyTypeEnum::EMAIL:
				break;
			default:
				throw new Exception("Only text and email properties are allowed in contains expression. The provided field was of type '$propertyType'.");
		}
		
		$column=constant($joiningFieldBaseConstName."Column");
		if($criteriaClass==$parentClass){
			// base class does not need an inner join
			$termName=$parentClass::getTableName();
			$theJoin=null;
		}else{
			$joinBasePlace=$criteriaClass::getTableName();
			$joinBaseColumn=$criteriaClass::getIDColumn();
			$joinColumn=$parentClass::getIDColumn();

			$joinName=Joiner::getJoinName($parentClass, JoinType::INNER,$joinBasePlace,$joinBaseColumn,$joinColumn);
			$termName=$joinName;
			$theJoin=Joiner::createJoin($parentClass,$joinBasePlace, $joinBaseColumn, $joinColumn, $joinName);
		}
		
		$term=$termName.".".$column." LIKE ?";
		$valueAsString="%".$value."%";
		$valueType=PropertyTypeEnum::getPreparedStmtType($propertyType);
		$values=[$valueAsString];
		$valueTypes=[$valueType];

		return new Expression($parentClass,$theJoin,$term,$values,$valueTypes);
	}
	
	/**
	 * Only allowed for text and email properties.
	 * 
	 * @param string $criteriaClass Class of the base entity, on which the criteria will be put.
	 * @param string $field A text property field (of the restriction object), on which the restriction shall take place.
	 * @param string $value Must be a string of length > 0.
	 * @param string $parentClass [optional] Actual parent class (if criteria class is SubEntity) that contains the specified field.
	 * @return Expression
	 */
	public static function endsWith($criteriaClass,$field,$value,$parentClass=null)
	{
		if($parentClass==null)
			$parentClass=$criteriaClass;
		$joiningFieldBaseConstName=$parentClass."::".$field;
		
		if($value===null)
			throw new Exception("Null value is not allowed for contains expression.");
		if(!is_string($value))
			throw new Exception("Value for contains expression must be a string.");
		
		/*@var $type FieldTypeEnum*/
		$type=constant($joiningFieldBaseConstName."FieldType");
		if($type!=FieldTypeEnum::PROPERTY)
			throw new Exception("Only property fields are allowed in contains expression. The provided field was of type '".$type."'.");
		
		/* @var $propertyType PropertyTypeEnum */
		$propertyType=constant($joiningFieldBaseConstName."PropertyType");
		switch($propertyType){
			case PropertyTypeEnum::TEXT:
			case PropertyTypeEnum::EMAIL:
				break;
			default:
				throw new Exception("Only text and email properties are allowed in contains expression. The provided field was of type '$propertyType'.");
		}
		
		$column=constant($joiningFieldBaseConstName."Column");
		if($criteriaClass==$parentClass){
			// base class does not need an inner join
			$termName=$parentClass::getTableName();
			$theJoin=null;
		}else{
			$joinBasePlace=$criteriaClass::getTableName();
			$joinBaseColumn=$criteriaClass::getIDColumn();
			$joinColumn=$parentClass::getIDColumn();

			$joinName=Joiner::getJoinName($parentClass, JoinType::INNER,$joinBasePlace,$joinBaseColumn,$joinColumn);
			$termName=$joinName;
			$theJoin=Joiner::createJoin($parentClass,$joinBasePlace, $joinBaseColumn, $joinColumn, $joinName);
		}
		
		$term=$termName.".".$column." LIKE ?";
		$valueAsString="%".$value;
		$valueType=PropertyTypeEnum::getPreparedStmtType($propertyType);
		$values=[$valueAsString];
		$valueTypes=[$valueType];

		return new Expression($parentClass,$theJoin,$term,$values,$valueTypes);
	}
	
	/**
	 * If the provided field is a ManyToOne, it will be compared to all notnull properties.
	 * 
	 * @param string $criteriaClass Class of the base entity, on which the criteria will be put.
	 * @param string $field Field (of the restriction object), on which the restriction shall take place.
	 * @param mixed $value Can be null. For ManyToOne fields, all properties that are not null will be included.
	 * @param string $parentClass [optional] Actual parent class (if criteria class is SubEntity) that contains the specified field.
	 * @return Mixed Can be a single expression, or multiple ones (if comparing by a manyToOne field, creates multiple expressions that checks for equality to all not-null properties).
	 */
	public static function equal($criteriaClass,$field,$value,$parentClass=null)
	{
		if($parentClass===null)
			$parentClass=$criteriaClass;
		$joiningFieldBaseConstName=$parentClass."::".$field;
		
		if($value===null){
			// if comparing for null, its always the same, no matter the type of the field
			$column=constant($joiningFieldBaseConstName."Column");
			if($criteriaClass===$parentClass){
				// base class does not need a join
				$termName=$criteriaClass::getTableName();
				$theJoin=null;
			}else{
				$joinBasePlace=$criteriaClass::getTableName();
				$joinBaseColumn=$criteriaClass::getIDColumn();
				$joinColumn=$parentClass::getIDColumn();

				$joinName=Joiner::getJoinName($parentClass, JoinType::INNER,$joinBasePlace,$joinBaseColumn,$joinColumn);
				$termName=$joinName;
				$theJoin=Joiner::createJoin($parentClass,$joinBasePlace, $joinBaseColumn, $joinColumn, $joinName);
			}
			$term=$termName.".".$column." IS NULL";
			
			return new Expression($parentClass,$theJoin,$term);
		}
		
		/*@var $type FieldTypeEnum*/
		$type=constant($joiningFieldBaseConstName."FieldType");
		switch($type){
			case FieldTypeEnum::PROPERTY:
				$column=constant($joiningFieldBaseConstName."Column");
				if($criteriaClass==$parentClass){
					// base class does not need an inner join
					$termName=$parentClass::getTableName();
					$theJoin=null;
				}else{
					$joinBasePlace=$criteriaClass::getTableName();
					$joinBaseColumn=$criteriaClass::getIDColumn();
					$joinColumn=$parentClass::getIDColumn();
					
					$joinName=Joiner::getJoinName($parentClass, JoinType::INNER,$joinBasePlace,$joinBaseColumn,$joinColumn);
					$termName=$joinName;
					$theJoin=Joiner::createJoin($parentClass,$joinBasePlace, $joinBaseColumn, $joinColumn, $joinName);
				}
				$term=$termName.".".$column."=?";
				$propertyType=constant($joiningFieldBaseConstName."PropertyType");
				$valueAsString=PropertyTypeEnum::convertToString($value, $propertyType);
				$valueType=PropertyTypeEnum::getPreparedStmtType($propertyType);
				$values=[$valueAsString];
				$valueTypes=[$valueType];
				
				return new Expression($parentClass,$theJoin,$term,$values,$valueTypes);
			case FieldTypeEnum::MANY_TO_ONE:
				// the $value is an Entity, check for all notnull PROPERTIES and use them for the expressions
				$expressions=[];
				
				$joins=[];
				
				$join=null;
				if($criteriaClass===$parentClass){
					// no need to join, can just use base entity
					$joinBasePlace=$criteriaClass::getTableName();
				}else{
					// has to join
					
					// join 1/2: the mandatory join of subEntityClass with the criteria class
					$mandatoryJoinBasePlace=$criteriaClass::getTableName();
					$mandatoryJoinBaseColumn=$criteriaClass::getIDColumn();
					$mandatoryJoinColumn=$parentClass::getIDColumn();
					$mandatoryJoinName=Joiner::getJoinName($parentClass, JoinType::INNER, $mandatoryJoinBasePlace, $mandatoryJoinBaseColumn, $mandatoryJoinColumn);
					$joins[$parentClass]=Joiner::createJoinArray($mandatoryJoinBasePlace, $mandatoryJoinBaseColumn, $mandatoryJoinColumn, $mandatoryJoinName);
					
					$joinBasePlace=$mandatoryJoinName;
				}
				
				/* @var $class FieldEntity */
				$class=constant($joiningFieldBaseConstName."Class");
				$column=constant($joiningFieldBaseConstName."Column");
				$fields=$class::getFieldList();
				/*@var $object FieldEntity*/
				$object=$value;
				
				$isSubEntity=is_subclass_of($object, SubEntity::class);
				
				if(!$isSubEntity){
					// let's check if only the ID is not null
					$isOnlyIDNotNull=true;
					foreach($fields as $field){
						if($field===StrongEntity::IDField)
							continue;
						if($object->$field!==null){
							$isOnlyIDNotNull=false;
							break;
						}
					}
					if($isOnlyIDNotNull){
						// no need to join anything, can just compare the column to the raw int value of the ID (treat it like a normal property)
						$value=$object->getID();
						$term="$joinBasePlace.$column=?";
						$valueAsString=PropertyTypeEnum::convertToString($value, PropertyTypeEnum::INT);
						$valueType=PropertyTypeEnum::getPreparedStmtType(PropertyTypeEnum::INT);
						$values=[$valueAsString];
						$valueTypes=[$valueType];

						return new Expression($parentClass,$join,$term,$values,$valueTypes);
					}
				}
				
				// join 2/2: the join of restriction object class with the subEntityClass
				// TODO what if the joining Entity Class and the base class are the same?
				// It is very unlikely, but it can happen.
				// It will happen when a table references itself ...
				$joinBaseColumn=$column;
				$joinColumn=$class::getIDColumn();
				$joinName=Joiner::getJoinName($class, JoinType::INNER, $joinBasePlace, $joinBaseColumn, $joinColumn);
				$joins[$class]=Joiner::createJoinArray($joinBasePlace, $joinBaseColumn, $joinColumn, $joinName);
				
				foreach($fields as $joinField){
					$joiningFieldBaseConstName="$class::$joinField";
					$type=constant($joiningFieldBaseConstName."FieldType");
					
					$value=$object->$joinField;
					switch($type){
						case FieldTypeEnum::PROPERTY:
							if($value!==null){
								$column=constant($joiningFieldBaseConstName."Column");
								$propertyType=constant($joiningFieldBaseConstName."PropertyType");
								$valueAsString=PropertyTypeEnum::convertToString($value, $propertyType);
								$term="$joinName.$column=?";
								$valueType=PropertyTypeEnum::getPreparedStmtType($propertyType);
								$values=[$valueAsString];
								$valueTypes=[$valueType];
								$expressions[]=new Expression($class,$joins,$term,$values,$valueTypes);
							}
							break;
						default:
							if($value!==null)
								trigger_error("Only fields of type PROPERTY are considered inside Expression::Equals.",E_USER_NOTICE);
							break;
					}
				}
				
				// check if it's a SubEntity to also include the ID (because the ID is in ManyToOne parent and is ignored in the above foreach ...
				if($isSubEntity){
					$id=$object->getID();
					if($id!==null){
						$column=$object->getIDColumn();
						$valueAsString=PropertyTypeEnum::convertToString($id, PropertyTypeEnum::INT);
						$term="$joinName.$column=?";
						$values=[$valueAsString];
						$valueTypes=["i"];
						$expressions[]=new Expression($class,$joins,$term,$values,$valueTypes);
					}
				}
				
				return $expressions;
			default:
				throw new Exception("The provided field is of unsupported field type '".$type."'.");
		}
	}
	
	/**
	 * Only allowed for text and email properties.
	 * 
	 * @param string $criteriaClass Class of the base entity, on which the criteria will be put.
	 * @param string $field A text property field (of the restriction object), on which the restriction shall take place.
	 * @param string $value Must be a string of length > 0.
	 * @param string $parentClass [optional] Actual parent class (if criteria class is SubEntity) that contains the specified field.
	 * @return Expression
	 */
	public static function startsWith($criteriaClass,$field,$value,$parentClass=null)
	{
		if($parentClass==null)
			$parentClass=$criteriaClass;
		$joiningFieldBaseConstName=$parentClass."::".$field;
		
		if($value===null)
			throw new Exception("Null value is not allowed for contains expression.");
		if(!is_string($value))
			throw new Exception("Value for contains expression must be a string.");
		
		/*@var $type FieldTypeEnum*/
		$type=constant($joiningFieldBaseConstName."FieldType");
		if($type!=FieldTypeEnum::PROPERTY)
			throw new Exception("Only property fields are allowed in contains expression. The provided field was of type '".$type."'.");
		
		/* @var $propertyType PropertyTypeEnum */
		$propertyType=constant($joiningFieldBaseConstName."PropertyType");
		switch($propertyType){
			case PropertyTypeEnum::TEXT:
			case PropertyTypeEnum::EMAIL:
				break;
			default:
				throw new Exception("Only text and email properties are allowed in contains expression. The provided field was of type '$propertyType'.");
		}
		
		$column=constant($joiningFieldBaseConstName."Column");
		if($criteriaClass==$parentClass){
			// base class does not need an inner join
			$termName=$parentClass::getTableName();
			$theJoin=null;
		}else{
			$joinBasePlace=$criteriaClass::getTableName();
			$joinBaseColumn=$criteriaClass::getIDColumn();
			$joinColumn=$parentClass::getIDColumn();

			$joinName=Joiner::getJoinName($parentClass, JoinType::INNER,$joinBasePlace,$joinBaseColumn,$joinColumn);
			$termName=$joinName;
			$theJoin=Joiner::createJoin($parentClass,$joinBasePlace, $joinBaseColumn, $joinColumn, $joinName);
		}
		
		$term=$termName.".".$column." LIKE ?";
		$valueAsString=$value."%";
		$valueType=PropertyTypeEnum::getPreparedStmtType($propertyType);
		$values=[$valueAsString];
		$valueTypes=[$valueType];

		return new Expression($parentClass,$theJoin,$term,$values,$valueTypes);
	}
	
	/**
	 * Puts an OR between all of the provided expressions.
	 * All expressions must have the same entity class.
	 * 
	 * @param array $expressions
	 * @return Expression
	 */
	public static function any($expressions)
	{
		// first, flatten the expressions (in case there are any arrays with inner expressions)
		$flattenedExpressions=[];
		while(count($expressions)>0){
			$newExpressions=[];
			
			foreach($expressions as $item){
				if(is_array($item)){
					$newExpressions=ArrayUtility::mergeTwo($newExpressions, $item);
				}else{
					$flattenedExpressions[]=$item;
				}
			}
			
			$expressions=$newExpressions;
		}
		
		$entityClass=$flattenedExpressions[0]->EntityClass;
		$mergedJoins=[];
		$mergedTerm="(";
		$mergedValues=[];
		$mergedValueTypes=[];
		$valueCount=0;
		
		$isFirst=true;
		foreach($flattenedExpressions as $expression){
			/*@var $expression Expression*/
			
			// merge all joins
			foreach($expression->Joins as $joiningEntityClass => $arrayByJoiningEntityClass){
				if(!isset($mergedJoins[$joiningEntityClass]))
					$mergedJoins[$joiningEntityClass]=[];
				$mergedArrayByJoiningEntityClass=$mergedJoins[$joiningEntityClass];

				foreach($arrayByJoiningEntityClass as $joinType => $arrayByJoinType){
					if(!isset($mergedArrayByJoiningEntityClass[$joinType]))
						$mergedArrayByJoiningEntityClass[$joinType]=[];
					$mergedArrayByJoinType=$mergedArrayByJoiningEntityClass[$joinType];

					foreach($arrayByJoinType as $joinBasePlace => $arrayByJoinBasePlace){
						if(!isset($mergedArrayByJoinType[$joinBasePlace]))
							$mergedArrayByJoinType[$joinBasePlace]=[];
						$mergedArrayByJoinBasePlace=$mergedArrayByJoinType[$joinBasePlace];

						foreach($arrayByJoinBasePlace as $joinBaseColumn => $arrayByJoinBaseColumn){
							if(!isset($mergedArrayByJoinBasePlace[$joinBaseColumn]))
								$mergedArrayByJoinBasePlace[$joinBaseColumn]=[];
							$mergedArrayByJoinBaseColumn=$mergedArrayByJoinBasePlace[$joinBaseColumn];

							foreach($arrayByJoinBaseColumn as $joinColumn => $joinName){
								if(!isset($mergedArrayByJoinBaseColumn[$joinColumn])){
									$mergedArrayByJoinBaseColumn[$joinColumn]=$joinName;

									$mergedArrayByJoinBasePlace[$joinBaseColumn]=$mergedArrayByJoinBaseColumn;
									$mergedArrayByJoinType[$joinBasePlace]=$mergedArrayByJoinBasePlace;
									$mergedArrayByJoiningEntityClass[$joinType]=$mergedArrayByJoinType;
									$mergedJoins[$joiningEntityClass]=$mergedArrayByJoiningEntityClass;
								}
							}
						}
					}
				}
			}
			
			if(!$isFirst)
				$mergedTerm.=" OR ";
			else
				$isFirst=false;
			
			$mergedTerm.="(".$expression->Term.")";
			
			for($i=$expression->ValueCount-1;$i>=0;$i--){
				$mergedValues[]=$expression->Values[$i];
				$mergedValueTypes[]=$expression->ValueTypes[$i];
				$valueCount++;
			}
		}
		
		$mergedTerm.=")";
		
		return new Expression($entityClass, $mergedJoins, $mergedTerm, $mergedValues, $mergedValueTypes);
	}
}
