<?php

namespace Combodo\iTop\Test\UnitTest\Application\TwigBase;

use Combodo\iTop\Portal\Twig\AppExtension;
use Combodo\iTop\Test\UnitTest\ItopDataTestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigTest extends ItopDataTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->RequireOnceItopFile('core/config.class.inc.php');
    }

    /**
     * @covers N°4384 N°7810
     *
     */
    public function testTemplate()
    {
        // Creating sandbox twig env. to load and test the custom form template
        $oTwig = new Environment(new FilesystemLoader(__DIR__.'/'));

        // Manually registering filters and functions as we didn't find how to do it automatically
        $oAppExtension = new AppExtension();
        $aFilters = $oAppExtension->getFilters();
        foreach ($aFilters as $oFilter)
        {
            $oTwig->addFilter($oFilter);
        }
        $aFunctions = $oAppExtension->getFunctions();
        foreach ($aFunctions as $oFunction)
        {
            $oTwig->addFunction($oFunction);
        }

        $sOutput = $oTwig->render('test.html.twig');

        $this->assertEquals(file_get_contents(__DIR__.'/test.html'), $sOutput);
    }
}