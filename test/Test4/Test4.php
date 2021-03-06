<?php

/*
 * Test4.php
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
 * @copyright Apr 26, 2017 Grega Mohorko
 */

require_once 'Student.php';
require_once 'Subject.php';
require_once 'Student_Subject.php';

use BlueDB\DataAccess\MySQL;
use BlueDB\DataAccess\Criteria\Expression;
use BlueDB\DataAccess\Criteria\Criteria;
use BlueDB\Configuration\BlueDBProperties;
use BlueDB\IO\JSON;
use BlueDB\Utility\EntityUtility;
use Test4\Student;
use Test4\Subject;
use Test4\Student_Subject;

/**
 * Tests loading, linking and unlinking using AssociativeEntities.
 * 
 * Also test expressions designed for associative entities.
 */
class Test4 extends Test
{
	public function run()
	{
		// set the namespace for entities (this can also be done in the config.ini file)
		BlueDBProperties::instance()->Namespace_Entities="Test4";
		
		// run the .sql script
		$sqlScript=file_get_contents("Test4/Test4.sql");
		if($sqlScript===false){
			echo "<b>Error:</b> Failed to read contents of Test4.sql.";
			return;
		}
		MySQL::queryMulti($sqlScript);
		
		$this->testLoadListForSide();
		$this->testLoadListForSideByCriteria();
		$this->testLink();
		$this->testJson();
		$this->testUnlink();
		$this->testUnlinkMultiple();
		$this->testLinkMultiple();
		$this->testExpressions();
	}
	
	private function testLoadListForSide()
	{
		// loads all subjects of Leon
		/* @var $leon Student */
		$leon=Student::loadByID(1);
		$subjects=Student_Subject::loadListForSide(Student_Subject::StudentsSide, $leon->ID);
		assert(count($subjects)===1,"Count of Leons subjects");
		assert($subjects[0]->Name==="Math","Leons subject");
		assert($subjects[0]->Students!==null,"Leons subjects");
		assert(count($subjects[0]->Students)===2,"ManyToMany field");
		assert($subjects[0]->Students[0]->Name==="Leon","ManyToMany field");
		assert($subjects[0]->Students[1]->Name==="Tadej","ManyToMany field");
		
		// loads all subjects of Matic, but only the Name field
		/* @var $matic Student */
		$matic=Student::loadByID(2);
		$subjects=Student_Subject::loadListForSide(Student_Subject::StudentsSide, $matic->ID,[Subject::NameField]);
		assert(count($subjects)===2,"Count of Matics subjects");
		assert($subjects[0]->Name==="History","Matics subject");
		assert($subjects[1]->Name==="Geography","Matics subject");
		assert($subjects[1]->Students===null,"ManyToMany field");
		
		// loads all subjects of Tadej, but without Name field
		/* @var $tadej Student */
		$tadej=Student::loadByID(3);
		$subjects=Student_Subject::loadListForSide(Student_Subject::StudentsSide, $tadej->ID, null, [Subject::NameField]);
		assert(count($subjects)===3,"Count of Tadejs subjects");
		assert($subjects[0]->Name===null,"Tadejs subject");
		assert($subjects[0]->ID===1,"Tadejs subject");
		assert($subjects[1]->ID===2,"Tadejs subject");
		assert($subjects[2]->ID===3,"Tadejs subject");
		assert($subjects[1]->Students!==null,"ManyToMany field");
		assert(count($subjects[2]->Students)===2,"ManyToMany field");
		
		// loads all students who are studying Math
		// Maths ID is 1
		$students=Student_Subject::loadListForSide(Student_Subject::SubjectsSide, 1);
		assert(count($students)===2,"Count of students studying Math");
		assert($students[0]->Name==="Leon","Students studying Math");
		assert($students[1]->Name==="Tadej","Students studying Math");
		
		// loads all subjects of Leon, but without ManyToMany fields
		$subjects=Student_Subject::loadListForSide(Student_Subject::StudentsSide, $leon->ID, null, null, null, null, false);
		assert(count($subjects)===1,"Count of Leons subjects");
		assert($subjects[0]->Name==="Math","Leons subject");
		assert($subjects[0]->Students===null,"Without ManyToMany fields");
	}
	
	private function testLoadListForSideByCriteria()
	{
		// load all subjects of Tadej that have the letter 'y' in it
		$criteria=new Criteria(Subject::class);
		$criteria->add(Expression::contains(Subject::class, Subject::NameField, "y"));
		$subjects=Student_Subject::loadListForSideByCriteria(Student_Subject::StudentsSide, 3, $criteria);
		assert(count($subjects)===2,"Count of Tadejs subjects with letter 'y'");
		assert($subjects[0]->Name==="History","Subjects of Tadej with letter 'y'");
		assert($subjects[1]->Name==="Geography","Subjects of Tadej with letter 'y'");
	}
	
	private function testLink()
	{
		// link Leon with Geography
		$leon=Student::loadByID(1);
		$geography=Subject::loadByID(3);
		Student_Subject::link($leon, $geography);
		$subjectsOfLeon=Student_Subject::loadListForSide(Student_Subject::StudentsSide, 1);
		assert(count($subjectsOfLeon)===2,"Linking Leon and Geography");
		assert($subjectsOfLeon[0]->Name==="Math","Linking Leon and Geography");
		assert($subjectsOfLeon[1]->Name==="Geography","Linking Leon and Geography");
	}
	
	private function testJson()
	{
		$leon=Student::loadByID(1);
		$matic=Student::loadByID(2);
		$tadej=Student::loadByID(3);
		
		// encode a single entity to JSON and then decode
		$json=JSON::encode($leon);
		$leonDecoded=JSON::decode($json);
		assert(EntityUtility::areEqual($leon, $leonDecoded),"JSON encode decode");
		
		// clone?
		$tadejClone=clone $tadej;
		assert(EntityUtility::areEqual($tadej, $tadejClone),"JSON encode decode clone");
		
		// encode and decode a list of entities
		$list=[$leon,$matic,$tadej];
		$json=JSON::encode($list);
		$list=JSON::decode($json);
		assert(EntityUtility::areEqual($leon, $list[0]),"JSON encode decode list");
		assert(EntityUtility::areEqual($matic, $list[1]),"JSON encode decode list");
		assert(EntityUtility::areEqual($tadej, $list[2]),"JSON encode decode list");
	}
	
	private function testUnlink()
	{
		// unlink Leon with Math
		$leon=Student::loadByID(1);
		$math=Subject::loadByID(1);
		Student_Subject::unlink($leon, $math);
		$subjectsOfLeon=Student_Subject::loadListForSide(Student_Subject::StudentsSide, 1);
		assert(count($subjectsOfLeon)===1,"Unlinking Leon and Math");
		assert($subjectsOfLeon[0]->Name==="Geography","Unlinking Leon and Math");
	}
	
	private function testUnlinkMultiple()
	{
		// unlink Tadej with all subjects
		/* @var $tadej Student */
		$tadej=Student::loadByID(3);
		Student_Subject::unlinkMultipleB($tadej, $tadej->Subjects,true,false);
		$tadejsSubjects=Student_Subject::loadListForSide(Student_Subject::StudentsSide, 3);
		assert(count($tadejsSubjects)===0,"Unlinking multiple subjects of Tadej");
		
		// unlink Geography with all students
		/* @var $geography Subject */
		$geography=Subject::loadByID(3);
		Student_Subject::unlinkMultipleA($geography, $geography->Students,false,true);
		$studentsOfGeography=Student_Subject::loadListForSide(Student_Subject::SubjectsSide, 3);
		assert(count($studentsOfGeography)===0,"Unlinking multiple students of Geography");
	}
	
	private function testLinkMultiple()
	{
		// link Tadej with all subjects
		$tadej=Student::loadByID(3);
		$allSubjects=Subject::loadList();
		Student_Subject::linkMultipleB($tadej, $allSubjects, true, false);
		$tadejsSubjects=Student_Subject::loadListForSide(Student_Subject::StudentsSide, 3);
		assert(count($tadejsSubjects)===count($allSubjects),"Linking multiple subjects with Tadej");
		assert($tadejsSubjects[0]->Name===$allSubjects[0]->Name,"Linking multiple subjects with Tadej");
		assert($tadejsSubjects[1]->Name===$allSubjects[1]->Name,"Linking multiple subjects with Tadej");
		assert($tadejsSubjects[2]->Name===$allSubjects[2]->Name,"Linking multiple subjects with Tadej");
		
		// link Math with all students who are not already
		$math=Subject::loadByID(1);
		$allStudents=Student::loadList();
		$studentsAlreadyInMath=Student_Subject::loadListForSide(Student_Subject::SubjectsSide, 1);
		for($i=count($studentsAlreadyInMath)-1;$i>=0;--$i){
			/* @var $studentAlreadyInMath Student */
			$studentAlreadyInMath=$studentsAlreadyInMath[$i];
			for($j=count($allStudents)-1;$j>=0;--$j){
				/* @var $student Student */
				$student=$allStudents[$j];
				if($student->getID()===$studentAlreadyInMath->getID()){
					unset($allStudents[$j]);
					break;
				}
			}
		}
		Student_Subject::linkMultipleA($math, $allStudents, false, true);
		$studentsOfMath=Student_Subject::loadListForSide(Student_Subject::SubjectsSide, 1);
		assert(count($studentsOfMath)===3,"Linking multiple students with Math");
		assert($studentsOfMath[0]->Name==="Leon","Linking multiple students with Math");
		assert($studentsOfMath[1]->Name==="Matic","Linking multiple students with Math");
		assert($studentsOfMath[2]->Name==="Tadej","Linking multiple students with Math");
	}
	
	private function testExpressions()
	{
		// first unlink Tadej and Geography
		$tadej=Student::loadByID(3);
		$geography=Subject::loadByID(3);
		Student_Subject::unlink($tadej, $geography,true,false);
		
		// load all subjects that nobody is connected to
		$criteria=new Criteria(Subject::class);
		$criteria->add(Expression::isNotIn(Subject::class, Student_Subject::class, Student_Subject::SubjectsSide));
		$subjects=Subject::loadListByCriteria($criteria);
		assert(count($subjects)===1,"Expression isNotIn");
		assert($subjects[0]->Name==="Geography","Expression isNotIn");
		
		// load all students that have no subjects
		$criteria=new Criteria(Student::class);
		$criteria->add(Expression::isNotIn(Student::class, Student_Subject::class, Student_Subject::StudentsSide));
		$students=Student::loadListByCriteria($criteria);
		assert(empty($students),"Expression isNotIn");
		
		// unlink Leon with his only subject (Math)
		$leon=Student::loadByID(1);
		$math=Subject::loadByID(1);
		Student_Subject::unlink($leon, $math,false,true);
		
		// now load all students that have no subjects again
		$students=Student::loadListByCriteria($criteria);
		assert(count($students)===1,"Expression isNotIn");
		assert($students[0]->Name==="Leon","Expression isNotIn");
	}
}
