<?php

/*
 * JoinHelper.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 25, 2017 Grega Mohorko
 */

namespace BlueDB\DataAccess;

/**
 * Manages joins.
 */
abstract class Joiner
{
	/**
	 * @var int
	 */
	private static $_joinNameCounter=0;
	
	/**
	 * @var array 5D: JoiningEntityClass -> JoinType -> JoinBasePlace -> JoinBaseColumn -> JoinColumn = JoinName
	 */
	private static $_joinNames=[];
	
	/**
	 * @param string $joiningEntityClass Entity class that is joining.
	 * @param string $joinType Type of the join.
	 * @param string $joinBasePlace Place of the base joining. Can be a table (from FROM) or a previously created join.
	 * @param string $joinBaseColumn Column of the joinBasePlace on which the join shall be made.
	 * @param string $joinColumn Column of the joining entity on which the join shall be made.
	 * @return string
	 */
	public static function getJoinName($joiningEntityClass,$joinType,$joinBasePlace,$joinBaseColumn,$joinColumn)
	{
		if(isset(self::$_joinNames[$joiningEntityClass])){
			$arrayByClass=&self::$_joinNames[$joiningEntityClass];
		}else{
			$arrayByClass=[];
			self::$_joinNames[$joiningEntityClass]=&$arrayByClass;
		}
		
		if(isset($arrayByClass[$joinType])){
			$arrayByJoinType=&$arrayByClass[$joinType];
		}else{
			$arrayByJoinType=[];
			$arrayByClass[$joinType]=&$arrayByJoinType;
		}
		
		if(isset($arrayByJoinType[$joinBasePlace])){
			$arrayByJoinBasePlace=&$arrayByJoinType[$joinBasePlace];
		}else{
			$arrayByJoinBasePlace=[];
			$arrayByJoinType[$joinBasePlace]=&$arrayByJoinBasePlace;
		}
		
		if(isset($arrayByJoinBasePlace[$joinBaseColumn])){
			$arrayByJoinBaseColumn=&$arrayByJoinBasePlace[$joinBaseColumn];
		}else{
			$arrayByJoinBaseColumn=[];
			$arrayByJoinBasePlace[$joinBaseColumn]=&$arrayByJoinBaseColumn;
		}
		
		if(isset($arrayByJoinBaseColumn[$joinColumn])){
			return $arrayByJoinBaseColumn[$joinColumn];
		}
		
		++self::$_joinNameCounter;
		$joinName="J".self::$_joinNameCounter;
		$arrayByJoinBaseColumn[$joinColumn]=$joinName;

		return $joinName;
	}
	
	/**
	 * Creates a join out of the specified values.
	 * 
	 * @param string $class
	 * @param JoinType $joinType
	 * @param string $joinBasePlace
	 * @param string $joinBaseColumn
	 * @param string $joinColumn
	 * @param string $joinName
	 * @return array
	 */
	public static function createJoin($class,$joinType,$joinBasePlace,$joinBaseColumn,$joinColumn,$joinName)
	{
		$theJoin=[];
		$theJoin[$class]=self::createJoinArray($joinType, $joinBasePlace, $joinBaseColumn, $joinColumn, $joinName);
		
		return $theJoin;
	}
	
	/**
	 * Creates a join array out of the specified values.
	 * 
	 * @param JoinType $joinType
	 * @param string $joinBasePlace
	 * @param string $joinBaseColumn
	 * @param string $joinColumn
	 * @param string $joinName
	 * @return array
	 */
	public static function createJoinArray($joinType,$joinBasePlace,$joinBaseColumn,$joinColumn,$joinName)
	{
		$type_BasePlace_BaseColumn=[];
		$type_BasePlace_BaseColumn[$joinColumn]=$joinName;
		$type_BasePlace=[];
		$type_BasePlace[$joinBaseColumn]=$type_BasePlace_BaseColumn;
		$typeJoin=[];
		$typeJoin[$joinBasePlace]=$type_BasePlace;
		$joinArray=[];
		$joinArray[$joinType]=$typeJoin;
		
		return $joinArray;
	}
}