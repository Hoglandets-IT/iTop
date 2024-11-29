<?php

/*
 * @copyright   Copyright (C) 2010-2024 Combodo SAS
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Test\UnitTest\Service;

use Combodo\iTop\Test\UnitTest\ItopCustomDatamodelTestCase;
use MFCoreModule;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RunTimeEnvironment;
use utils;


/**
 * Class UnitTestRunTimeEnvironment
 *
 * Runtime env. dedicated to creating a temp. environment for a group of unit tests with XML deltas.
 *
 * @author Guillaume Lajarige <guillaume.lajarige@combodo.com>
 * @since N°6097 2.7.10 3.0.4 3.1.1
 */
class UnitTestRunTimeEnvironment extends RunTimeEnvironment
{
	/**
	 * @var string[]
	 */
	protected $aCustomDatamodelFiles = null;

    /**
     * @var string
     */
    protected $sSourceEnv;

    public function __construct($sSourceEnv, $sTargetEnv)
    {
        parent::__construct($sTargetEnv);
        $this->sSourceEnv = $sSourceEnv;
    }

    public function GetEnvironment(): string
	{
		return $this->sFinalEnv;
	}

    public function IsUpToDate()
    {
        clearstatcache();
	    $aCustomDatamodelFiles = $this->GetCustomDatamodelFiles();
	    $sDestDir = APPROOT.'env-'.$this->sFinalEnv;
	    if (! is_dir($sDestDir) && count($aCustomDatamodelFiles) > 0){
		    $this->PrintFiles($aCustomDatamodelFiles);
		    return false;
		}

	    $fLastCompilationTime = filemtime($sDestDir);
		if (false === $fLastCompilationTime){
			echo "Issue when calling filemtime($sDestDir) \n";
			return true;
		}

	    $aModifiedFiles = [];
	    $this->FindFilesModifiedAfter($fLastCompilationTime, APPROOT.'datamodels/2.x', $aModifiedFiles);
	    $this->FindFilesModifiedAfter($fLastCompilationTime, APPROOT.'extensions', $aModifiedFiles);
	    $this->FindFilesModifiedAfter($fLastCompilationTime, APPROOT.'data/production-modules', $aModifiedFiles);

		foreach ($aCustomDatamodelFiles as $sCustomDatamodelFile) {
            if (filemtime($sCustomDatamodelFile) > $fLastCompilationTime) {
                $aModifiedFiles[] = $sCustomDatamodelFile;
            }
        }

        if (count($aModifiedFiles) > 0) {
	        $this->PrintFiles($aModifiedFiles);
			return false;
        }

		return true;
    }

	private function PrintFiles(array $aFiles, string $sMsg = "The following files have been modified after the last compilation") : void
	{
		echo "$sMsg:\n";
		foreach ($aFiles as $sFile) {
			echo " - $sFile\n";
		}
		echo "\n";
	}

    /**
	 * @inheritDoc
	 */
	protected function GetMFModulesToCompile($sSourceEnv, $sSourceDir)
	{
		$aRet = parent::GetMFModulesToCompile($sSourceEnv, $sSourceDir);

		foreach ($this->GetCustomDatamodelFiles() as $sDeltaFile) {
			$sDeltaId = preg_replace('/[^\d\w]/', '', $sDeltaFile);
            $sDeltaName = basename($sDeltaFile);
            $sDeltaDir = dirname($sDeltaFile);
			$oDelta = new MFCoreModule($sDeltaName, "$sDeltaDir/$sDeltaName", $sDeltaFile);
			$aRet[$sDeltaId] = $oDelta;
		}
		return $aRet;
	}

	public function GetCustomDatamodelFiles()
	{
		if (!is_null($this->aCustomDatamodelFiles)) {
			return $this->aCustomDatamodelFiles;
		}
		$this->aCustomDatamodelFiles = [];

		// Search for the PHP files implementing the method GetDatamodelDeltaAbsPath
		// and extract the delta file path from the method
		// APPROOT, APPROOT/extensions/*, APPROOT/data/production-modules/*, APPROOT/data/production-modules/*/*

		$aTestDirs = [];
		foreach(['', 'extensions/*/', 'data/production-modules/*/', 'data/production-modules/*/*/'] as $sRoot) {
			$aTestDirs = array_merge($aTestDirs, glob(APPROOT.$sRoot.'tests', GLOB_ONLYDIR));
		}

		$aLoadedTestClasses = [];
		foreach($aTestDirs as $sTestDir) {
			// Iterate on all PHP files in subdirectories
			// Note: grep is not available on Windows, so we will use the PHP Reflection API
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sTestDir)) as $oFile) {
				if ($oFile->isDir()){
					continue;
				}
				if (! utils::EndsWith($oFile->getFilename(), 'Test.php')) {
					continue;
				}
				$sFile = $oFile->getPathname();
				$sContent = file_get_contents($sFile);
				if (strpos($sContent, 'GetDatamodelDeltaAbsPath') === false) {
					continue;
				}
				$sClass = '';
				$aMatches = [];
				if (preg_match('/namespace\s+([^;]+);/', $sContent, $aMatches)) {
					$sNamespace = $aMatches[1];
					$sClass = $sNamespace.'\\'.basename($sFile, '.php');
				}
				if (preg_match('/\s+class\s+([^ ]+)\s+/', $sContent, $aMatches)) {
					$sClass = $sNamespace.'\\'.$aMatches[1];
				}
				if ($sClass === '') {
					continue;
				}

				if (in_array($sClass, $aLoadedTestClasses)) {
					continue;
				}

				$aLoadedTestClasses[]=$sClass;
                require_once $sFile;

				$oReflectionClass = new ReflectionClass($sClass);
				if ($oReflectionClass->isAbstract()) {
					continue;
				}
                // Check if the class extends ItopCustomDatamodelTestCase
                if (!$oReflectionClass->isSubclassOf(ItopCustomDatamodelTestCase::class)) {
                    continue;
                }
				/** @var \Combodo\iTop\Test\UnitTest\ItopCustomDatamodelTestCase $oTestClassInstance */
				$oTestClassInstance = new $sClass();
				if ($oTestClassInstance->GetTestEnvironment() !== $this->sFinalEnv) {
					continue;
				}
				$sDeltaFile = $oTestClassInstance->GetDatamodelDeltaAbsPath();
				if (!is_file($sDeltaFile)) {
					throw new \Exception("Unknown delta file: $sDeltaFile, from test class '$sClass'");
				}
				if (!in_array($sDeltaFile, $this->aCustomDatamodelFiles)) {
					$this->aCustomDatamodelFiles[] = $sDeltaFile;
				}
			}
		}

		$this->PrintFiles($this->aCustomDatamodelFiles, "Found XML delta files");
		return $this->aCustomDatamodelFiles;
	}

    private function FindFilesModifiedAfter(float $fReferenceTimestamp, string $sPathToScan, array &$aModifiedFiles)
    {
        if (!is_dir($sPathToScan)) {
            return;
        }
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sPathToScan)) as $oFile) {
            if ($oFile->isDir()) {
                continue;
            }
            if (filemtime($oFile->getPathname()) > $fReferenceTimestamp) {
                $aModifiedFiles[] = $oFile->getPathname();
            }
        }
    }
}