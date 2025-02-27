<?php
// Copyright (c) 2010-2018 Combodo SARL
//
//   This file is part of iTop.
//
//   iTop is free software; you can redistribute it and/or modify
//   it under the terms of the GNU Affero General Public License as published by
//   the Free Software Foundation, either version 3 of the License, or
//   (at your option) any later version.
//
//   iTop is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU Affero General Public License for more details.
//
//   You should have received a copy of the GNU Affero General Public License
//   along with iTop. If not, see <http://www.gnu.org/licenses/>
//

namespace Combodo\iTop\Test\UnitTest\Core;

use Combodo\iTop\Test\UnitTest\ItopDataTestCase;
use Combodo\iTop\Test\UnitTest\ItopTestCase;
use CoreException;
use CoreServices;
use CoreUnexpectedValue;
use RestResultListOperations;
use SimpleGraphException;


class RestServicesTest extends ItopDataTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    // provider

    /**
     * @return void
     * @dataProvider providerTestSanitizeJsonInput
     */
    public function testSanitizeJsonInput($sJsonData, $sExpectedJsonDataSanitized)
    {
        $oRS = new CoreServices();
        $sOutputJson = $oRS->SanitizeJsonInput($sJsonData);
        $this->assertEquals($sExpectedJsonDataSanitized, $sOutputJson);
    }

    public function testCoreUpdateSanitization()
    {
        $sJsonData = <<<JSON
{
   "operation": "core/update",
   "comment": "Update user",
   "class": "UserLocal",
   "key":
   {
      "description": "The fridge is empty"
   },
   "output_fields": "first_name, password",
   "fields":
   {
      "id": "1",
      "password" : "123456"
   }
}
JSON;
        $sExpectedJsonDataSanitized = <<<JSON
{
   "operation": "core/update",
   "comment": "Update user",
   "class": "UserLocal",
   "key":
   {
      "description": "My description"
   },
   "output_fields": "first_name, password",
   "fields":
   {
      "id": "1",
      "password" : "123456"
   }
}
JSON;

        $sOutputJson = $this->CallCoreRestApi_Internally($sJsonData);
        $aJson = json_decode($sOutputJson, true);
        $this->assertEquals(0, $aJson['code'], $sOutputJson); // answer is still the same
        $this->assertEquals($sExpectedJsonDataSanitized, $aJson['input_data'], $sOutputJson);
    }

    /**
     * @param $sOperation
     * @param $aJsonData
     * @param $sExpectedJsonDataSanitized
     * @return void
     * @throws CoreException
     * @throws CoreUnexpectedValue
     * @throws SimpleGraphException
     * @dataProvider providerTestSanitizeJsonOutput
     */
    public function testSanitizeJsonOutput($sOperation, $aJsonData, $sExpectedJsonDataSanitized)
    {
        $oRS = new CoreServices();
        $oResult = $oRS->ExecOperation('1.3', $sOperation, $aJsonData);

        $oResult->SanitizeContent();
        $this->assertEquals($sExpectedJsonDataSanitized, json_encode($oResult));
    }

    public function providerTestSanitizeJsonInput()
    {
        return [
                'core/check_credentials' => [
                    '{"operation": "core/check_credentials", "user": "admin", "password": "admin"}',
                    '{"operation": "core/check_credentials", "user": "admin", "password": "*****"}'
                ],
                'core/update' => [
                    '{"operation": "core/update", "comment": "Update user", "class": "UserLocal", "key": {"description": "My description"}, "output_fields": "first_name, password", "fields": {"id": "1", "password" : "123456"}}',
                    '{"operation": "core/update", "comment": "Update user", "class": "UserLocal", "key": {"description": "My description"}, "output_fields": "first_name, password", "fields": {"id": "1", "password" : "*****"}}'
                ],
            'core/create' => [
                '{"operation": "core/create", "comment": "Create user", "class": "UserLocal", "fields": {"first_name": "John", "last_name": "Doe", "email": "jd@example/com", "password" : "123456"}}',
                '{"operation": "core/create", "comment": "Create user", "class": "UserLocal", "fields": {"first_name": "John", "last_name": "Doe", "email": "jd@example/com", "password" : "*****"}}',
                ],
        ];
    }

    public function providerTestSanitizeJsonOutput()
    {
        return [
            'core/check_credentials' => [
                'core/check_credentials',
                ['user' => 'admin', 'password' => 'admin'],
                '{"operation":"core/check_credentials","user":"admin","password":"*****"}'
            ],
            'core/update' => [
                'core/update',
                ['comment' => 'Update user', 'class' => 'UserLocal', 'key' => ['description' => 'My description'], 'output_fields' => 'first_name, password', 'fields' => ['id' => '1', 'password' => '123456']],
                '{"operation":"core/update","comment":"Update user","class":"UserLocal","key":{"description":"My description"},"output_fields":"first_name, password","fields":{"id":"1","password":"*****"}}'
            ],
            'core/create' => [
                'core/create',
                ['comment' => 'Create user', 'class' => 'UserLocal', 'fields' => ['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'jd@example/com', 'password' => '123456']],
                '{"operation":"core/create","comment":"Create user","class":"UserLocal","fields":{"first_name":"John","last_name":"Doe","email":"jd@example/com","password":"*****"}}'
            ],
            'core/get' => [
                'core/get',
                ['comment' => 'Get user', 'class' => 'UserLocal', 'key' => ['id' => '1'], 'output_fields' => 'first_name, password'],
                '{"operation":"core/get","comment":"Get user","class":"UserLocal","key":{"id":"1"},"output_fields":"first_name, password"}'
            ],
        ];
    }
}
