<?php
declare(strict_types=1);
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

use ArchivedObjectException;
use Combodo\iTop\Test\UnitTest\ItopCustomDatamodelTestCase;
use CoreException;
use CoreUnexpectedValue;
use Exception;
use MetaModel;
use PasswordTest;
use RestResultWithObjects;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @backupGlobals disabled
 */
class RestServicesSanitizeOutputTest extends ItopCustomDatamodelTestCase
{
    private const SIMPLE_PASSWORD = '123456';

    /**
     * @return void
     * @throws CoreException
     */
    public function testSanitizeJsonOutputOnSimpleAttribute()
    {
        $oContactTest = MetaModel::NewObject('ContactTest', [
                'password' => self::SIMPLE_PASSWORD]);
        $oRestResultWithObject = new RestResultWithObjects();
        $oRestResultWithObject->AddObject(0, 'ok', $oContactTest, ['ContactTest' => ['password']]);
        $oRestResultWithObject->SanitizeContent();
        static::assertEquals(
                '{"objects":{"ContactTest::-1":{"code":0,"message":"ok","class":"ContactTest","key":-1,"fields":{"password":"*****"}}},"code":0,"message":null}',
                json_encode($oRestResultWithObject));
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testSanitizeJsonOutputAttributeExternalKeyOnNNRelation()
    {
        $oContactTest = $this->createObject('ContactTest', [
                'password' => self::SIMPLE_PASSWORD]);

        $oTestServer = $this->createObject('TestServer', [
                'name' => 'test_server',
        ]);


        // create lnkContactTestToServer
        $oLnkContactTestToServer = $this->createObject('lnkContactTestToServer', [
                'contact_test_id' => $oContactTest->GetKey(),
                'test_server_id' => $oTestServer->GetKey()
        ]);

        $oRestResultWithObject = new RestResultWithObjects();
        $oRestResultWithObject->AddObject(0, 'ok', $oLnkContactTestToServer,
                ['lnkContactTestToServer' => ['contact_test_password']]);

        $oRestResultWithObject->SanitizeContent();

        static::assertContains(
                '*****',
                json_encode($oRestResultWithObject));
        static::assertNotContains(
                self::SIMPLE_PASSWORD,
                json_encode($oRestResultWithObject));

    }

    /**
     * @throws Exception
     */
    public function testSanitizeJsonOutputAttributeOnNNRelation()
    {
        $oContactTest = $this->createObject('ContactTest', [
                'password' => self::SIMPLE_PASSWORD]);

        $oTestServer = $this->createObject('TestServer', [
                'name' => 'test_server',
        ]);


        // create lnkContactTestToServer
        $this->createObject('lnkContactTestToServer', [
                'contact_test_id' => $oContactTest->GetKey(),
                'test_server_id' => $oTestServer->GetKey()
        ]);

        $oRestResultWithObject = new RestResultWithObjects();
        $oRestResultWithObject->AddObject(0, 'ok', $oTestServer,
                ['TestServer' => ['contact_list']]);

        $oRestResultWithObject->SanitizeContent();
        static::assertContains(
                '*****',
                json_encode($oRestResultWithObject));
        static::assertNotContains(
                self::SIMPLE_PASSWORD,
                json_encode($oRestResultWithObject));
    }


    /**
     * @throws CoreException
     * @throws CoreUnexpectedValue
     * @throws ArchivedObjectException
     * @throws Exception
     */
    public function testSanitizeJsonOutputOn1NRelation()
    {
        $oTestServer = $this->createObject('TestServer', [
                'name' => 'my_server',
        ]);

        $oPassword = new PasswordTest();
        $oPassword->Set('password', self::SIMPLE_PASSWORD);
        $oPassword->Set('server_test_id', $oTestServer->GetKey());

        $oContactList = $oTestServer->Get('contact_list');
        $oContactList->AddItem($oPassword);
        $oTestServer->Set('contact_list', $oContactList);

        $oRestResultWithObject = new RestResultWithObjects();
        $oRestResultWithObject->AddObject(0, 'ok', $oTestServer, ['TestServer' => ['id', 'password_list']]);
        $oRestResultWithObject->SanitizeContent();
        static::assertEquals(
                '{"objects":{"TestServer::-1":{"code":0,"message":"ok","class":"TestServer","key":-1,"fields":{"password_list":["*****"]}}},"code":0,"message":null}',
                json_encode($oRestResultWithObject));

    }

    /**
     * @return string Abs path to the XML delta to use for the tests of that class
     */
    public function GetDatamodelDeltaAbsPath(): string
    {
        return __DIR__ . '/Delta/delta_test_sanitize_output.xml';
    }
}
