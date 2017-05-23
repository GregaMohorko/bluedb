<?php

/*
 * Subject.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 29, 2017 Grega Mohorko
 */

namespace Test6;

use BlueDB\Entity\StrongEntity;
use BlueDB\Entity\FieldTypeEnum;
use BlueDB\Entity\PropertyTypeEnum;

class Subject extends StrongEntity
{
	public static function getTableName() { return "Subject"; }
	
	/**
	 * @var string
	 */
	public $Name;
	const NameField="Name";
	const NameFieldType=FieldTypeEnum::PROPERTY;
	const NameColumn=self::NameField;
	const NamePropertyType=PropertyTypeEnum::TEXT;
	/**
	 * @var array
	 */
	public $Attendances;
	const AttendancesField="Attendances";
	const AttendancesFieldType=FieldTypeEnum::ONE_TO_MANY;
	const AttendancesClass=StudentAttendance::class;
	const AttendancesIdentifier=StudentAttendance::SubjectField;
	/**
	 * @var array
	 */
	public $Students;
	const StudentsField="Students";
	const StudentsFieldType=FieldTypeEnum::MANY_TO_MANY;
	const StudentsClass=StudentAttendance::class;
	const StudentsSide=StudentAttendance::SubjectsSide;
}