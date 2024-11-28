<?php

declare(strict_types=1);

namespace Siel\Acumulus\Tests\PrestaShop;

use Module;
use Siel\Acumulus\Helpers\Container;
use Siel\Acumulus\Tests\AcumulusTestUtils as BaseAcumulusTestUtils;

use function dirname;

/**
 * AcumulusTestUtils contains PS specific test functionalities
 */
trait AcumulusTestUtils
{
    use BaseAcumulusTestUtils {
        copyLatestTestSources as protected parentCopyLatestTestSources;
        updateTestSources as protected parentUpdateTestSources;
    }

    protected static function getAcumulusContainer(): Container
    {
        /** @var \Acumulus $module */
        $module = Module::getInstanceByName('acumulus');
        return $module->getAcumulusContainer();
    }

    protected function getTestsPath(): string
    {
        return dirname(__FILE__, 2);
    }

    /**
     * @noinspection UntrustedInclusionInspection
     */
    public function copyLatestTestSources(): void
    {
        static $hasRun = false;

        if (!$hasRun) {
            $hasRun = true;
            require_once dirname(__FILE__, 2) . '/bootstrap-acumulus.php';
        }
        $this->parentCopyLatestTestSources();
    }

    public function updateTestSources(): void
    {
        static $hasRun = false;

        if (!$hasRun) {
            $hasRun = true;
            require_once dirname(__FILE__, 2) . '/bootstrap-acumulus.php';
        }
        $this->parentUpdateTestSources();
    }
}
