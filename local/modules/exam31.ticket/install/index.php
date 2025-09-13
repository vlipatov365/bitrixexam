<?php
B_PROLOG_INCLUDED === true || die();

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\EventManager;
use Bitrix\Main\UrlRewriter;
use Bitrix\Main\SystemException;
use Exam31\Ticket\SomeElementTable;
use Exam31\Ticket\ORM\SomeElementInfoTable;

Loc::loadMessages(__FILE__);

class exam31_ticket extends CModule
{
	var $MODULE_ID = 'exam31.ticket';

	protected string $fileRoot;
	protected array $filesPath;

	/**
	 * @return string
	 */
	public static function getModuleId()
	{
		return basename(dirname(__DIR__));
	}

	public function __construct()
	{
		$arModuleVersion = array();
		include(dirname(__FILE__) . '/version.php');
		$this->MODULE_ID = self::getModuleId();
		$this->MODULE_VERSION = $arModuleVersion['VERSION'];
		$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		$this->MODULE_NAME = Loc::getMessage('EXAM31_TICKET_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('EXAM31_TICKET_MODULE_DESC');
		$this->PARTNER_NAME = Loc::getMessage('EXAM31_TICKET_NAME');
		$this->PARTNER_URI = Loc::getMessage('EXAM31_TICKET_URI');

		$this->fileRoot = Application::getDocumentRoot();

		$this->filesPath = [
			'PUBLIC' => '/exam31',
			'COMPONENTS' => '/local/components/' . $this->MODULE_ID,
			'ACTIVITIES' => '/local/activities/examticketactivity',
		];

	}

	protected function checkDir(): bool
	{
		foreach ($this->filesPath as $path)
		{
			if (Directory::isDirectoryExists($this->fileRoot . $path))
			{
				throw new SystemException('Directory  "' . $path . '"  exist. To avoid data loss, check and delete already installed directories that will be installed from the module. Installation cancelled.');
				return false;
			}
		}
		
		return true;
	}

	public function DoInstall()
	{

		try
		{
			if ($this->checkdir())
			{
				ModuleManager::registerModule($this->MODULE_ID);

				$this->InstallDB();
				$this->InstallEvents();
				$this->InstallFiles();
				$this->InstallUrlRewriterRuls();
			}
		}
		catch (Throwable $t)
		{
			global $APPLICATION;
			$APPLICATION->throwException($t->getMessage());

			return false;
		}

		return true;

	}

	public function DoUninstall()
	{
		try
		{
			$this->UnInstallUrlRewriterRuls();
			$this->UnInstallFiles();
			$this->UnInstallEvents();
			$this->UnInstallDB();

			ModuleManager::unRegisterModule($this->MODULE_ID);
		}
		catch (Throwable $t)
		{
			global $APPLICATION;
			$APPLICATION->throwException($t->getMessage());

			return false;
		}

		return true;
	}

	public function InstallDB(): bool
	{
		if (!Loader::includeModule($this->MODULE_ID))
		{
			return false;
		}

		$dbConnection = Application::getConnection();
        $tables = $this->getTables();
        foreach ($tables as $tableClass => $demoCallable) {
            $entity = $tableClass::getEntity();
            $tableName = $tableClass::getTableName();
            if (!$dbConnection->isTableExists($tableName))
            {
                $entity->createDbTable();
                try {
                    if (is_callable($demoCallable)) {
                        $demoCallable();
                    }
                } catch (Throwable $e) {
                    continue;
                }
            }
        }

		return true;
	}


	public function UnInstallDB(): bool
	{
		if (!Loader::includeModule($this->MODULE_ID))
		{
			return false;
		}

		$dbConnection = Application::getConnection();
        $tables = $this->getTables();
        foreach ($tables as $tableClass => $demoCallable) {
            $tableName = $tableClass::getTableName();
            if ($dbConnection->isTableExists($tableName))
            {
                $dbConnection->dropTable($tableName);
            }
        }

		return true;
	}

	public function InstallEvents(): void
	{
		$eventManager = EventManager::getInstance();

		$eventManager->registerEventHandlerCompatible(
			'main',
			'OnUserTypeBuildList',
			$this->MODULE_ID,
			'Exam31\\Ticket\\ExamFieldType',
			'getUserTypeDescription'
		);
	}

	public function UnInstallEvents(): void
	{
		$eventManager = EventManager::getInstance();

		$eventManager->unRegisterEventHandler(
			'main',
			'OnUserTypeBuildList',
			$this->MODULE_ID,
			'Exam31\\Ticket\\ExamFieldType',
			'getUserTypeDescription'
		);
	}

	public function InstallFiles(): void
	{
		copyDirFiles(
			$this->fileRoot . '/local/modules/' . $this->MODULE_ID . '/install/activities/examticketactivity',
			$this->fileRoot . '/local/activities/examticketactivity',
			true,
			true
		);
		copyDirFiles(
			$this->fileRoot . '/local/modules/' . $this->MODULE_ID . '/install/components',
			$this->fileRoot . '/local/components/' . $this->MODULE_ID,
			true,
			true
		);
		copyDirFiles(
			$this->fileRoot . '/local/modules/' . $this->MODULE_ID . '/install/public/exam31',
			$this->fileRoot . '/exam31',
			true,
			true
		);
	}

	public function UnInstallFiles(): void
	{
		Directory::deleteDirectory($this->fileRoot . '/local/activities/examticketactivity');
		Directory::deleteDirectory($this->fileRoot . '/local/components/' . $this->MODULE_ID);
		Directory::deleteDirectory($this->fileRoot . '/exam31');
	}

	public function InstallUrlRewriterRuls(): void
	{
		UrlRewriter::add('s1',
			[
				'ID' => 'exam31.ticket:examelements',
				'CONDITION' => '#^/exam31/#',
				'PATH' => '/exam31/index.php',
			]
		);
	}

	public function UnInstallUrlRewriterRuls(): void
	{
		UrlRewriter::delete('s1',
			[
				'ID' => 'exam31.ticket:examelements',
				'CONDITION' => '#^/exam31/#',
				'PATH' => '/exam31/index.php',
			]
		);
	}

    private function getTables(): array
    {
        return [
            SomeElementTable::class => function() {
                for($i=1; $i <= 100; $i++) {
                    SomeElementTable::add([
                        'ACTIVE' => true,
                        'TITLE' => Loc::getMessage('EXAM31_DEMO_NAME', ['#I#' => $i]),
                        'TEXT' => Loc::getMessage('EXAM31_DEMO_DESC', ['#I#' => $i]),
                    ]);
                }
            },
            SomeElementInfoTable::class => function () {
                for ($i=1; $i <=20; $i++) {
                    SomeElementInfoTable::add([
                        'TITLE' => Loc::getMessage('EXAM31_DEMO_INFO', ['#I#' => $i]),
                        'ELEMENT_ID' => random_int(1, 5)
                    ]);
                }
            },
        ];
    }
}

