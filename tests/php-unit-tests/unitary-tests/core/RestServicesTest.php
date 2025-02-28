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
use CoreException;
use CoreServices;
use CoreUnexpectedValue;
use SimpleGraphException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @backupGlobals disabled
 */
class RestServicesTest extends ItopDataTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

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

    public function providerTestSanitizeJsonInput()
    {
        return [
                'core/check_credentials' => [
                        '{"operation": "core/check_credentials", "user": "admin", "password": "admin"}',
                        '{
    "operation": "core/check_credentials",
    "user": "admin",
    "password": "*****"
}'
                ],
                'core/update' => [
                        '{"operation": "core/update", "comment": "Update user", "class": "UserLocal", "key": {"id":1}, "output_fields": "first_name, password", "fields": {"password" : "123456"}}',
                        '{
    "operation": "core/update",
    "comment": "Update user",
    "class": "UserLocal",
    "key": {
        "id": 1
    },
    "output_fields": "first_name, password",
    "fields": {
        "password": "*****"
    }
}'
                ],
                'core/create' => [
                        '{"operation": "core/create", "comment": "Create user", "class": "UserLocal", "fields": {"first_name": "John", "last_name": "Doe", "email": "jd@example/com", "password" : "123456"}}',
                        '{
    "operation": "core/create",
    "comment": "Create user",
    "class": "UserLocal",
    "fields": {
        "first_name": "John",
        "last_name": "Doe",
        "email": "jd@example/com",
        "password": "*****"
    }
}'
                ],
        ];
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
        $this->CreateUser('my_example', '1', 'Azertyuiiop*12', 1);
        $oRS = new CoreServices();
        $oResult = $oRS->ExecOperation(1.3, $sOperation, json_decode(json_encode($aJsonData)));
        // delete every pattern like "::xxx"
        $actualResult = json_encode($oResult);
        $sExpectedJsonDataSanitized = preg_replace('/::[0-9]+/', '', $sExpectedJsonDataSanitized);
        $actualResult = preg_replace('/::[0-9]+/', '', $actualResult);
        // convert both to arrays
        $actualResult = json_decode($actualResult, true);
        $sExpectedJsonDataSanitized = json_decode($sExpectedJsonDataSanitized, true);
        $this->recursive_unset($actualResult, 'key');
        $this->recursive_unset($sExpectedJsonDataSanitized, 'key');


        $this->assertEquals($sExpectedJsonDataSanitized, $actualResult);
    }

    public function providerTestSanitizeJsonOutput()
    {
        return [

                'core/update' => [
                        'core/update',
                        ['comment' => 'Update user', 'class' => 'UserLocal', 'key' => ['login' => 'my_example'], 'output_fields' => 'password', 'fields' => ['password' => 'opkB!req57']],
                        '{"objects":{"UserLocal::78":{"code":0,"message":"updated","class":"UserLocal","key":"78","fields":{"password":"*****"}}},"code":0,"message":null}'],
                'core/create' => [
                        'core/create',
                        ['comment' => 'Create user', 'class' => 'UserLocal', 'fields' => ['password' => 'Azertyuiiop*12', 'login' => 'toto', 'profile_list' => [1]]],
                        '{"operation":"core/create","comment":"Create user","class":"UserLocal","fields":{"first_name":"John","last_name":"Doe","email":"jd@example/com","password":"*****"}}'
                ],
                'core/get' => [
                        'core/get',
                        ['comment' => 'Get user', 'class' => 'UserLocal', 'key' => ['login' => 'my_example'], 'output_fields' => 'first_name, password'],
                        '{"objects":{"UserLocal":{"code":0,"message":"","class":"UserLocal","key":"148","fields":{"first_name":"My first name","password":"*****"}}},"code":0,"message":"Found: 1"}'
                ],
                'core/check_credentials' => [
                        'core/check_credentials',
                        ['user' => 'admin', 'password' => 'admin'],
                        '{"code":0,"message":null,"authorized":true}'
                ],
        ];
    }

    function recursive_unset(&$array, $unwanted_key) {
        unset($array[$unwanted_key]);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursive_unset($value, $unwanted_key);
            }
        }
    }
}
