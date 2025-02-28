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
use UserLocal;

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
        $oUser = new UserLocal();
        $oUser->Set('password', "123456");
        $oRestResultWithObject = new \RestResultWithObjects();
        $oRestResultWithObject->AddObject(0, "ok", $oUser, ['UserLocal' => ['login', 'password']]);
        $oRestResultWithObject->SanitizeContent();
        $this->assertEquals($sExpectedJsonDataSanitized, json_encode($oRestResultWithObject));
    }

    public function providerTestSanitizeJsonOutput()
    {
        return [

                'core/update' => [
                        'core/update',
                        ['comment' => 'Update user', 'class' => 'UserLocal', 'key' => ['login' => 'my_example'], 'output_fields' => 'password', 'fields' => ['password' => 'opkB!req57']],
                        '{"objects":{"UserLocal::-1":{"code":0,"message":"ok","class":"UserLocal","key":-1,"fields":{"login":"******","password":"******"}}},"code":0,"message":null}'
                ],
                'core/create' => [
                        'core/create',
                        ['comment' => 'Create user', 'class' => 'UserLocal', 'fields' => ['password' => 'Azertyuiiop*12', 'login' => 'toto', 'profile_list' => [1]]],
                        '{"objects":{"UserLocal::-1":{"code":0,"message":"ok","class":"UserLocal","key":-1,"fields":{"login":"******","password":"******"}}},"code":0,"message":null}'
                ],
                'core/get' => [
                        'core/get',
                        ['comment' => 'Get user', 'class' => 'UserLocal', 'key' => ['login' => 'my_example'], 'output_fields' => 'first_name, password'],
                        '{"objects":{"UserLocal::-1":{"code":0,"message":"ok","class":"UserLocal","key":-1,"fields":{"login":"******","password":"******"}}},"code":0,"message":null}'
                ],
                'core/check_credentials' => [
                        'core/check_credentials',
                        ['user' => 'admin', 'password' => 'admin'],
                        '{"objects":{"UserLocal::-1":{"code":0,"message":"ok","class":"UserLocal","key":-1,"fields":{"login":"******","password":"******"}}},"code":0,"message":null}'                ],
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
