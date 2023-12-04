<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Functional\Command;

use FilesystemIterator;
use Netlogix\Nxerrorhandler\Command\GenerateErrorPagesCommand;
use Netlogix\Nxerrorhandler\Service\ConfigurationService;
use PHPUnit\Framework\Attributes\Test;
use ReflectionObject;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class GenerateErrorPagesCommandTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/nxerrorhandler'];

    protected array $pathsToLinkInTestInstance = [
        'typo3conf/ext/nxerrorhandler/Tests/Functional/Fixtures/Sites/' => 'typo3conf/sites',
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'core' => [
                        // disable cache for site configurations
                        'backend' => NullBackend::class,
                    ],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->purgeCreatedDirectoriesAndFiles();
    }

    protected function purgeCreatedDirectoriesAndFiles()
    {
        if (is_dir(ConfigurationService::getErrorDocumentDirectory())) {
            GeneralUtility::rmdir(ConfigurationService::getErrorDocumentDirectory(), true);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->purgeCreatedDirectoriesAndFiles();
    }

    #[Test]
    public function itCreatesErrorDocumentDirectory(): void
    {
        self::assertDirectoryDoesNotExist(ConfigurationService::getErrorDocumentDirectory());

        $subject = $this->createMock(GenerateErrorPagesCommand::class);

        $reflectionObject = new ReflectionObject($subject);
        $reflectionMethod = $reflectionObject->getMethod('initialize');
        $reflectionMethod->setAccessible(true);

        $reflectionMethod->invokeArgs($subject, [new StringInput(''), new NullOutput()]);

        self::assertDirectoryExists(ConfigurationService::getErrorDocumentDirectory());
    }

    #[Test]
    public function itCreatesHtaccessInErrorDocumentDirectory(): void
    {
        self::assertFileDoesNotExist(ConfigurationService::getErrorDocumentDirectory() . '.htaccess');

        $subject = $this->createMock(GenerateErrorPagesCommand::class);

        $reflectionObject = new ReflectionObject($subject);
        $reflectionMethod = $reflectionObject->getMethod('initialize');
        $reflectionMethod->setAccessible(true);

        $reflectionMethod->invokeArgs($subject, [new StringInput(''), new NullOutput()]);

        self::assertFileExists(ConfigurationService::getErrorDocumentDirectory() . '.htaccess');
    }

    #[Test]
    public function itDoesNotCreateErrorDocumentsWithoutSiteConfiguration(): void
    {
        self::assertDirectoryDoesNotExist(ConfigurationService::getErrorDocumentDirectory());

        $subject = new GenerateErrorPagesCommand();
        $subject->run(new StringInput(''), new NullOutput());

        $iter = new FilesystemIterator(
            ConfigurationService::getErrorDocumentDirectory(),
            FilesystemIterator::SKIP_DOTS
        );
        // there should only be .htaccess
        self::assertEquals(1, iterator_count($iter));
    }

    #[Test]
    public function itDoesNotCreateErrorDocumentsIfSiteConfigurationDoesNotHaveErrorDocumentConfigured(): void
    {
        self::assertDirectoryDoesNotExist(ConfigurationService::getErrorDocumentDirectory());

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->setUpFrontendRootPage(1);

        $subject = new GenerateErrorPagesCommand();
        $subject->run(new StringInput(''), new NullOutput());

        $iter = new FilesystemIterator(
            ConfigurationService::getErrorDocumentDirectory(),
            FilesystemIterator::SKIP_DOTS
        );
        // there should only be .htaccess
        self::assertEquals(1, iterator_count($iter));
    }

    #[Test]
    public function itCreates400ErrorDocumentsForSite(): void
    {
        self::assertDirectoryDoesNotExist(ConfigurationService::getErrorDocumentDirectory());

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->setUpFrontendRootPage(1);

        $subject = new GenerateErrorPagesCommand();
        $subject->run(new StringInput(''), new NullOutput());

        self::assertDirectoryExists(ConfigurationService::getErrorDocumentDirectory());

        self::assertDirectoryExists(ConfigurationService::getErrorDocumentDirectory() . '400');
        $iter = new FilesystemIterator(
            ConfigurationService::getErrorDocumentDirectory() . '400',
            FilesystemIterator::SKIP_DOTS
        );
        // it will create one file per language = 2 files total
        self::assertEquals(2, iterator_count($iter));
    }
}
