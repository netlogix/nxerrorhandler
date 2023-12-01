<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Functional\ErrorHandler\Component;

use Netlogix\Nxerrorhandler\ErrorHandler\Component\StaticDocumentComponent;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;

class StaticDocumentComponentTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/nxerrorhandler'];

    protected array $pathsToLinkInTestInstance = [
        'typo3conf/ext/nxerrorhandler/Tests/Functional/Fixtures/Sites/' => 'typo3conf/sites',
    ];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'nxerrorhandler' => [],
        ],
    ];

    /**
     * @test
     */
    public function itTriesToFetchContentForLanguageCombinations()
    {
        $errorCode = 400;
        $request = new ServerRequest('/de/', 'GET');
        $request = $request->withAttribute('site', (new SiteFinder())->getSiteByPageId(1));
        $request = $request->withAttribute('language', new SiteLanguage(1, 'de_DE.UTF-8', new Uri('/de/'), []));

        $subject = $this->getMockBuilder(StaticDocumentComponent::class)
            ->onlyMethods(['getContentFromPath'])
            ->getMock();
        $subject->expects(self::at(0))->method('getContentFromPath')->willReturnCallback(
            function (string $errorDocumentFileName) use ($errorCode): ?string {
                self::assertStringEndsWith('/' . $errorCode . '/-1-1.html', $errorDocumentFileName);

                return null;
            }
        );
        $subject->expects(self::at(1))->method('getContentFromPath')->willReturnCallback(
            function (string $errorDocumentFileName) use ($errorCode): ?string {
                self::assertStringEndsWith('/' . $errorCode . '/-1-0.html', $errorDocumentFileName);

                return null;
            }
        );
        $subject->expects(self::at(2))->method('getContentFromPath')->willReturnCallback(
            function (string $errorDocumentFileName) use ($errorCode): ?string {
                self::assertStringEndsWith('/' . $errorCode . '/-1-0.html', $errorDocumentFileName);

                return null;
            }
        );

        $subject->getOutput($errorCode, $request);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->setUpFrontendRootPage(1);
    }
}
