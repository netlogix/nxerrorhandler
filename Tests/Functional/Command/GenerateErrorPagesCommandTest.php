<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Functional\Command;

use FilesystemIterator;
use Netlogix\Nxerrorhandler\Command\GenerateErrorPagesCommand;
use Netlogix\Nxerrorhandler\Service\ConfigurationService;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GenerateErrorPagesCommandTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = ['typo3conf/ext/nxerrorhandler'];

    protected $configurationToUseInTestInstance = [
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

    public function setUp(): void
    {
        parent::setUp();

        $this->purgeCreatedDirectoriesAndFiles();
    }

    protected function purgeCreatedDirectoriesAndFiles()
    {
        if (is_dir(ConfigurationService::getErrorDocumentDirectory())) {
            GeneralUtility::rmdir(ConfigurationService::getErrorDocumentDirectory(), true);
        }

        // delete all created sites
        if (is_dir(Environment::getConfigPath() . '/sites')) {
            GeneralUtility::rmdir(Environment::getConfigPath() . '/sites', true);
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->purgeCreatedDirectoriesAndFiles();
    }

    /**
     * @test
     */
    public function itCreatesErrorDocumentDirectory()
    {
        self::assertDirectoryDoesNotExist(ConfigurationService::getErrorDocumentDirectory());

        $subject = $this->getMockBuilder(GenerateErrorPagesCommand::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->callInaccessibleMethod($subject, 'initialize', new StringInput(''), new NullOutput());

        self::assertDirectoryExists(ConfigurationService::getErrorDocumentDirectory());
    }

    /**
     * @test
     */
    public function itCreatesHtaccessInErrorDocumentDirectory()
    {
        self::assertFileDoesNotExist(ConfigurationService::getErrorDocumentDirectory() . '.htaccess');

        $subject = $this->getMockBuilder(GenerateErrorPagesCommand::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->callInaccessibleMethod($subject, 'initialize', new StringInput(''), new NullOutput());

        self::assertFileExists(ConfigurationService::getErrorDocumentDirectory() . '.htaccess');
    }

    /**
     * @test
     */
    public function itDoesNotCreateErrorDocumentsWithoutSiteConfiguration()
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

    /**
     * @test
     */
    public function itDoesNotCreateErrorDocumentsIfSiteConfigurationDoesNotHaveErrorDocumentConfigured()
    {
        self::assertDirectoryDoesNotExist(ConfigurationService::getErrorDocumentDirectory());

        $this->importDataSet('ntf://Database/pages.xml');
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

    /**
     * @test
     */
    public function itCreates400ErrorDocumentsForSite()
    {
        self::assertDirectoryDoesNotExist(ConfigurationService::getErrorDocumentDirectory());

        $this->importDataSet('ntf://Database/pages.xml');
        $this->setUpFrontendRootPage(1, [], [
            1 => 'EXT:nxerrorhandler/Tests/Functional/Fixtures/Frontend/site.yaml',
        ]);

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
