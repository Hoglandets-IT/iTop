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

use Combodo\iTop\Test\UnitTest\ItopCustomDatamodelTestCase;
use MetaModel;
use PasswordTest;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @backupGlobals disabled
 */
class RestServicesSanitizeOutputTest extends iTopCustomDatamodelTestCase
{
    public function testSanitizeJsonOutputOnSimpleAttribute()
    {
        // inserer en base ?
        // insererer contact list ?
        // requeter des champs qui ne s'affichent pas
        $oContactTest = MetaModel::NewObject('ContactTest', array(
                'password' => '123456'));
        $oRestResultWithObject = new \RestResultWithObjects();
        $oRestResultWithObject->AddObject(0, "ok", $oContactTest, ['ContactTest' => ['password']]);
        $oRestResultWithObject->SanitizeContent();
        $this->assertEquals(
                '{"objects":{"ContactTest::-1":{"code":0,"message":"ok","class":"ContactTest","key":-1,"fields":{"password":"*****"}}},"code":0,"message":null}',
                json_encode($oRestResultWithObject));
    }

    public function testSanitizeJsonOutputAttributeExternalKeyOnNNRelation()
    {
        $oContactTest = $this->CreateObject('ContactTest', array(
                'password' => '123456'));

        $oTestServer = $this->CreateObject('TestServer', [
                'name' => 'testserver',
        ]);


        // create lnkContactTestToServer
        $oLnkContactTestToServer = $this->CreateObject('lnkContactTestToServer', array(
                'contact_test_id' => $oContactTest->GetKey(),
                'testserver_id' => $oTestServer->GetKey()
        ));

        $oRestResultWithObject = new \RestResultWithObjects();
        $oRestResultWithObject->AddObject(0, "ok", $oLnkContactTestToServer,
                ['lnkContactTestToServer' => ['contact_test_password']]);

        $oRestResultWithObject->SanitizeContent();
        $this->assertEquals(
                '{"objects":{"}',
                json_encode($oRestResultWithObject));
    }

    public function testSanitizeJsonOutputAttributeOnNNRelation()
    {
        $oContactTest = $this->CreateObject('ContactTest', array(
                'password' => '123456'));

        $oTestServer = $this->CreateObject('TestServer', [
                'name' => 'testserver',
        ]);


        // create lnkContactTestToServer
        $this->CreateObject('lnkContactTestToServer', array(
                'contact_test_id' => $oContactTest->GetKey(),
                'testserver_id' => $oTestServer->GetKey()
        ));

        $oRestResultWithObject = new \RestResultWithObjects();
        $oRestResultWithObject->AddObject(0, "ok", $oTestServer,
                ['TestServer' => ['contact_list']]);

        $oRestResultWithObject->SanitizeContent();
        $this->assertEquals(
                '{"objects":{"}',
                json_encode($oRestResultWithObject));
    }


    public function testSanitizeJsonOutputOn1NRelation()
    {
        // Impossible to query the class
        $oTestServer = $this->CreateObject('TestServer', [
                'name' => 'my_server',
        ]);

        $oPassword = new PasswordTest();
        $oPassword->Set('password', "123456");
        $oPassword->Set('server_test_id', $oTestServer->GetKey());

        $oContactList = $oTestServer->Get('contact_list');
        $oContactList->AddItem($oPassword);
        $oTestServer->Set('contact_list', $oContactList);

        $oRestResultWithObject = new \RestResultWithObjects();
        $oRestResultWithObject->AddObject(0, "ok", $oTestServer, ['TestServer' => ['id', 'password_list']]);
        $oRestResultWithObject->SanitizeContent();
        $this->assertEquals(
                '{"objects":{"TestServer::-1":{"code":0,"message":"ok","class":"TestServer","key":-1,"fields":{"password_list":["*****"]}}},"code":0,"message":null}',
                json_encode($oRestResultWithObject));

    }

    public function GetDatamodelDeltaAbsPath(): string
    {
        return  __DIR__ . "/Delta/delta_test_sanitize_output.xml";
    }
}
