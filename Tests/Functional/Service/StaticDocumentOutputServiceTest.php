<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Functional\Service;

use Override;
use Netlogix\Nxerrorhandler\Service\StaticDocumentOutputService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class StaticDocumentOutputServiceTest extends FunctionalTestCase
{
    protected array $pathsToLinkInTestInstance = [
        'typo3conf/ext/nxerrorhandler/Tests/Functional/Fixtures/Sites' => 'typo3conf/sites',
    ];

    protected array $testExtensionsToLoad = ['typo3conf/ext/nxerrorhandler'];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'nxerrorhandler' => [],
        ],
    ];

    #[Test]
    public function itTriesToFetchContentForLanguageCombinations(): void
    {
        $errorCode = 400;
        $request = new ServerRequest('/de/', 'GET');

        $request = $request->withAttribute('site', $this->get(SiteFinder::class)->getSiteByPageId(1));
        $request = $request->withAttribute('language', new SiteLanguage(1, 'de_DE.UTF-8', new Uri('/de/'), []));

        $subject = $this->getAccessibleMock(StaticDocumentOutputService::class, ['getContentFromPath']);

        $matcher = $this->exactly(3);

        $subject
            ->expects($matcher)
            ->method('getContentFromPath')
            ->willReturnCallback(static function (string $errorDocumentFileName) use (
                $errorCode,
                $matcher,
            ): ?string {
                match ($matcher->numberOfInvocations()) {
                    1 => self::assertStringEndsWith('/' . $errorCode . '/1-1.html', $errorDocumentFileName),
                    2, 3 => self::assertStringEndsWith('/' . $errorCode . '/1-0.html', $errorDocumentFileName),
                };

                return null;
            });

        $subject->getOutput($errorCode, $request);
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->setUpFrontendRootPage(1);
    }
}
