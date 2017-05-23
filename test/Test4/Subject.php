<?php

/*
 * Subject.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 26, 2017 Grega Mohorko
 */

namespace Test4;

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
	public $Students;
	const StudentsField="Students";
	const StudentsFieldType=FieldTypeEnum::MANY_TO_MANY;
	const StudentsClass=Student_Subject::class;
	const StudentsSide=Student_Subject::SubjectsSide;
}