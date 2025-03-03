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
use Group;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @backupGlobals disabled
 */
class RestServicesSanitizeOutputTest extends iTopCustomDatamodelTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }


    public function testSanitizeJsonOutput()
    {
        $oGroup = new Group();
        $oGroup->Set('encrypted_string', "123456");
        $oRestResultWithObject = new \RestResultWithObjects();
        $oRestResultWithObject->AddObject(0, "ok", $oGroup, ['Group' => ['encrypted_string']]);
        $oRestResultWithObject->SanitizeContent();
        $this->assertEquals('{"objects":{"Group::-1":{"code":0,"message":"ok","class":"Group","key":-1,"fields":{"encrypted_string":"*****"}}},"code":0,"message":null}', json_encode($oRestResultWithObject));
    }



    public function GetDatamodelDeltaAbsPath(): string
    {
        return  __DIR__ . "/Delta/delta_test_sanitize_output.xml";
    }
}
