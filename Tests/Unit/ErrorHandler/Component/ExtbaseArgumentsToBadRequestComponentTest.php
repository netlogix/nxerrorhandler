<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Unit\ErrorHandler\Component;

use Exception;
use Netlogix\Nxerrorhandler\ErrorHandler\Component\ExtbaseArgumentsToBadRequestComponent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\Exception\RequiredArgumentMissingException;
use TYPO3\CMS\Extbase\Property\Exception as PropertyException;
use TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ExtbaseArgumentsToBadRequestComponentTest extends UnitTestCase
{
    #[DataProvider('exceptionHeaderStatusDataProvider')]
    #[Test]
    public function itCanGetAdditionalHeadersForExceptionTypes(Exception $e, string $status): void
    {
        $subject = new ExtbaseArgumentsToBadRequestComponent();
        $res = $subject->getHttpHeaders($e);

        self::assertCount(1, $res);
        self::assertEquals($status, $res[0]);
    }

    #[Test]
    public function itDoesNotReturnStatusForUnmappedException(): void
    {
        $subject = new ExtbaseArgumentsToBadRequestComponent();
        $res = $subject->getHttpHeaders(new Exception());

        self::assertCount(0, $res);
    }

    public static function exceptionHeaderStatusDataProvider(): array
    {
        return [
            TargetNotFoundException::class => [new TargetNotFoundException(), HttpUtility::HTTP_STATUS_404],
            PropertyException::class => [new PropertyException(), HttpUtility::HTTP_STATUS_400],
            RequiredArgumentMissingException::class => [
                new RequiredArgumentMissingException(),
                HttpUtility::HTTP_STATUS_400,
            ],
        ];
    }
}
