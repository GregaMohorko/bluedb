<?php

/*
 * Test3.php
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
 * @copyright Apr 21, 2017 Grega Mohorko
 */

require_once 'Address.php';
require_once 'User.php';
require_once 'Student.php';
require_once 'Teacher.php';

use BlueDB\DataAccess\MySQL;
use BlueDB\DataAccess\Criteria\Criteria;
use BlueDB\DataAccess\Criteria\Expression;
use BlueDB\Configuration\BlueDBProperties;
use BlueDB\IO\JSON;
use BlueDB\Utility\EntityUtility;
use Test3\User;
use Test3\Student;
use Test3\Teacher;
use Test3\Address;

/**
 * Tests loading, updating, saving and deleting SubEntities.
 */
class Test3 extends Test
{
	public function run()
	{
		// set the namespace for entities (this can also be done in the config.ini file)
		BlueDBProperties::instance()->Namespace_Entities="Test3";
		
		// run the .sql script
		$sqlScript=file_get_contents("Test3/Test3.sql");
		if($sqlScript===false){
			echo "<b>Error:</b> Failed to read contents of Test3.sql.";
			return;
		}
		MySQL::queryMulti($sqlScript);
		
		$this->testLoadList();
		$this->testLoadListByCriteria();
		$this->testLoadSingle();
		$this->testExists();
		$this->testJson();
		$this->testUpdate();
		$this->testSave();
		$this->testDelete();
	}
	
	private function testLoadList()
	{
		// should load all students
		$students=Student::loadList();
		assert(count($students)===2,"Count of all students");
		// checks if all data is correct (see Test3.sql)
		$this->checkLojzi($students[0]);
		$this->checkTadej($students[1]);
		
		// should load all teachers
		$teachers=Teacher::loadList();
		assert(count($teachers)===1,"Count of all teachers");
		$this->checkGrega($teachers[0]);
		
		// should load all users (in type User)
		$allUsers=User::loadList();
		assert(count($allUsers)===3,"Count of all users");
		assert(get_class($allUsers[0])===User::class,"Users class");
		assert($allUsers[1]->getID()===2,"Users ID");
		assert($allUsers[2]->Name==="Grega","Users name");
		assert($allUsers[0]->Address!=null,"Users address");
		assert($allUsers[1]->Address->Street==="Maribor","Users address street");
		
		// should load all students, but only RegistrationNumber for each
		$students=Student::loadList([Student::RegistrationNumberField]);
		assert(count($students)===2,"Count of all students");
		assert($students[0]->RegistrationNumber==="E1066934","Students registration number");
		assert($students[1]->User->Name===null,"Students name should be null, but is '".$students[1]->User->Name."'.");
		assert($students[0]->User->Address===null,"Students address");
		
		// should load all teachers, but only Address for each
		$teachers=Teacher::loadList([User::AddressField]);
		assert(count($teachers)===1,"Count of all teachers");
		assert($teachers[0]->User->Address!==null,"Teachers address");
		assert($teachers[0]->User->Address->Street==="Celje","Teachers address street");
		assert($teachers[0]->User->Name===null,"Teachers name");
		
		// should load all students, but only Name for each
		$students=Student::loadList([User::NameField]);
		assert(count($students)===2,"Count of all students");
		assert($students[0]->RegistrationNumber===null,"Students registration number");
		assert($students[1]->User->Name==="Tadej","Students name");
		assert($students[0]->User->Address===null,"Students address");
		
		// should load all students, but without RegistrationNumber
		$students=Student::loadList(null,[Student::RegistrationNumberField]);
		assert(count($students)===2,"Count of all students");
		assert($students[0]->RegistrationNumber===null,"Students registration number");
		assert($students[1]->User->Name==="Tadej","Students name");
		assert($students[0]->User->Address!==null,"Students address");
		assert($students[0]->User->Address->Street==="Ljubljana","Students address street");
		
		// should load all students, but without Name
		$students=Student::loadList(null,[User::NameField]);
		assert(count($students)===2,"Count of all students");
		assert($students[0]->RegistrationNumber==="E1066934","Students registration number");
		assert($students[0]->User->Name===null,"Students name");
		assert($students[0]->User->Address!==null,"Students address");
		assert($students[0]->User->Address->Street==="Ljubljana","Students address street");
	}
	
	private function testLoadListByCriteria()
	{
		// should load all students whose address is 'Maribor'
		$criteria=new Criteria(Student::class);
		$maribor=new Address();
		$maribor->Street="Maribor";
		$criteria->add(Expression::equal(Student::class, User::AddressField, $maribor,User::class));
		$students=Student::loadListByCriteria($criteria);
		assert(count($students)===1,"Count of students");
		$this->checkTadej($students[0]);
		
		// should load all students whose address street is 'Maribor' and ID is 42
		$criteria->add(Expression::equal(Student::class, User::IDField, 42,User::class));
		$students=Student::loadListByCriteria($criteria);
		assert(count($students)===0,"Count of students");
		
		// should load all students whose address street is 'Maribor' and ID is 2
		$criteria=new Criteria(Student::class);
		$maribor->setID(2);
		$criteria->add(Expression::equal(Student::class, User::AddressField, $maribor, User::class));
		$students=Student::loadListByCriteria($criteria);
		assert(count($students)===1,"Count of students");
		$this->checkTadej($students[0]);
		
		// should load all users whose address street is 'Ljubljana' or name is 'Tadej'
		$criteria=new Criteria(Student::class);
		$ljubljana=new Address();
		$ljubljana->Street="Ljubljana";
		$expressions=[];
		$expressions[]=Expression::equal(Student::class, User::AddressField, $ljubljana,User::class);
		$expressions[]=Expression::equal(Student::class, User::NameField, "Tadej",User::class);
		$criteria->add(Expression::any($expressions));
		$students=Student::loadListByCriteria($criteria);
		assert(count($students)===2,"Count of students");
		$this->checkLojzi($students[0]);
		$this->checkTadej($students[1]);
	}
	
	private function testLoadSingle()
	{
		// should load the teacher with ID 3
		$grega=Teacher::loadByID(3);
		$this->checkGrega($grega);
	}
	
	private function testExists()
	{
		assert(Student::exists(Student::RegistrationNumberField, null)===false,"Exists student with null registration number");
		assert(Student::exists(Student::RegistrationNumberField,"E1066934")===true,"Exists student with registration number");
		assert(Student::exists(User::NameField,"Grega",User::class)===false,"Exists student with name 'Grega'");
		assert(Teacher::exists(User::NameField, "Grega", User::class)===true,"Exists teacher with name 'Grega'");
		
		// check if there exists a student with address of street 'Ljubljana'
		$criteria=new Criteria(Student::class);
		$ljubljana=new Address();
		$ljubljana->Street="Ljubljana";
		$criteria->add(Expression::equal(Student::class, User::AddressField, $ljubljana,User::class));
		assert(Student::existsByCriteria($criteria)===true,"Exists student with address Ljubljana");
		
		// check if there exists a teacher with address of street 'Ljubljana'
		$criteria=new Criteria(Teacher::class);
		$criteria->add(Expression::equal(Teacher::class, User::AddressField, $ljubljana, User::class));
		assert(Teacher::existsByCriteria($criteria)===false,"Exists teacher with address Ljubljana");
	}
	
	private function testJson()
	{
		$lojzi=Student::loadByID(1);
		$tadej=Student::loadByID(2);
		$grega=Teacher::loadByID(3);
		
		// encode a single entity to JSON and then decode
		$json=JSON::encode($lojzi);
		$lojziDecoded=JSON::decode($json);
		assert(EntityUtility::areEqual($lojzi, $lojziDecoded),"JSON encode decode");
		
		// clone?
		$tadejClone=clone $tadej;
		assert(EntityUtility::areEqual($tadej, $tadejClone),"JSON encode decode clone");
		
		// encode and decode a list of entities
		$list=[$lojzi,$tadej,$grega];
		$json=JSON::encode($list);
		$list=JSON::decode($json);
		assert(EntityUtility::areEqual($lojzi, $list[0]),"JSON encode decode list");
		assert(EntityUtility::areEqual($tadej, $list[1]),"JSON encode decode list");
		assert(EntityUtility::areEqual($grega, $list[2]),"JSON encode decode list");
	}
	
	private function testUpdate()
	{
		// change registration number of Lojzi
		/* @var $lojzi Student */
		$lojzi=Student::loadByID(1);
		$lojzi->RegistrationNumber="newNumber";
		Student::update($lojzi,true,false,[Student::RegistrationNumberField]);
		// check if it was really changed
		$lojzi=Student::loadByID(1);
		assert($lojzi->RegistrationNumber==="newNumber","Changing Lojzis registration number");
		
		// rename Tadej to Jernej
		/* @var $tadej Student */
		$tadej=Student::loadByID(2);
		$tadej->User->Name="Jernej";
		Student::update($tadej,false,true,[User::NameField]);
		// check if Tadej is now really Jernej
		/* @var $jernej Student */
		$jernej=Student::loadByID(2);
		assert($jernej->User->Name==="Jernej","Renaming Tadej, Tadejs name should be 'Jernej', but is '".$jernej->User->Name."'");
	}
	
	private function testSave()
	{
		// create new student, Katja
		$katja=new Student();
		$katja->RegistrationNumber="katjasRegNumber";
		$katja->User=new User();
		$katja->User->Name="Katja";
		$katja->User->Address=new Address();
		$katja->User->Address->Street="Zalec";
		Address::save($katja->User->Address, true, false);
		Student::save($katja,false,true);
		/* @var $katja Student */
		$katja=Student::loadByID($katja->getID());
		assert($katja->RegistrationNumber==="katjasRegNumber","Creating new student");
		assert($katja->User->Name==="Katja","Creating new student");
		assert($katja->User->Address->Street==="Zalec","Creating new student");
	}
	
	private function testDelete()
	{
		// delete Student with ID 1
		/* @var $student Student */
		$student=Student::loadByID(1);
		Student::delete($student,true,false);
		Address::delete($student->User->Address,false,true);
		assert(!Student::exists(User::IDField, 1,User::class),"Deleting a student");
		assert(!User::exists(User::IDField, 1),"Deleting parent tables of SubEntity");
		assert(!Address::exists(Address::IDField, $student->User->Address->ID),"Deleting ManyToOne field");
	}
	
	/**
	 * @param Student $lojzi
	 */
	private function checkLojzi($lojzi)
	{
		assert($lojzi->RegistrationNumber=="E1066934","Lojzis registration number");
		assert($lojzi->User!=null,"Lojzis parent field");
		assert($lojzi->User->ID===1,"Lojzis ID");
		assert($lojzi->User->Name==="Lojzi","Lojzis name");
		assert($lojzi->User->Address!=null,"Lojzis address");
		assert($lojzi->User->Address->Street==="Ljubljana","Lojzis address street");
		assert(count($lojzi->User->Address->Users)===1,"Lojzis address users count");
		assert($lojzi->User->Address->Users[0]->Name==="Lojzi","Lojzis address users name");
	}
	
	/**
	 * @param Student $tadej
	 */
	private function checkTadej($tadej)
	{
		assert($tadej->RegistrationNumber=="E1068321","Tadejs registration number");
		assert($tadej->User!=null,"Tadejs parent field");
		assert($tadej->User->ID===2,"Tadejs ID");
		assert($tadej->User->Name==="Tadej","Tadejs name");
		assert($tadej->User->Address!=null,"Tadejs address");
		assert($tadej->User->Address->Street==="Maribor","Tadejs address street");
		assert(count($tadej->User->Address->Users)===1,"Tadejs address users count");
		assert($tadej->User->Address->Users[0]->Name==="Tadej","Tadejs address users name");
	}
	
	/**
	 * @param Teacher $grega
	 */
	private function checkGrega($grega)
	{
		assert($grega->User!=null,"Gregas parent field");
		assert($grega->getID()===3,"Gregas ID");
		assert($grega->User->Name==="Grega","Gregas name");
		assert($grega->User->Address!=null,"Gregas address");
		assert($grega->User->Address->Street==="Celje","Gregas address street");
		assert(count($grega->User->Address->Users)===1,"Gregas address users count");
		assert($grega->User->Address->Users[0]->Name==="Grega","Gregas address users name");
	}
}
