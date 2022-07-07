<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Functional\ErrorHandler\Component;

use Netlogix\Nxerrorhandler\ErrorHandler\Component\StaticDocumentComponent;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;

class StaticDocumentComponentTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = ['typo3conf/ext/nxerrorhandler'];

    protected $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'nxerrorhandler' => [

            ],
        ]
    ];

    /**
     * @test
     *
     * @return void
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

        $this->importDataSet('ntf://Database/pages.xml');
        $this->setUpFrontendRootPage(1, [], [1 => 'EXT:nxerrorhandler/Tests/Functional/Fixtures/Frontend/site.yaml']);
    }
}
