<?php
/**
 * @noinspection AutoloadingIssuesInspection
 */

declare(strict_types=1);

/**
 * Class AcumulusTestsBootstrap bootstraps the Acumulus tests.
 */
class AcumulusTestsBootstrap
{
    protected static AcumulusTestsBootstrap $instance;

    protected Shop $prestashop;

    /**
     * Setup the unit testing environment.
     */
    public function __construct()
    {
        ini_set('display_errors', 'on');
        error_reporting(E_ALL);

        // Ensure server variable is set for email functions.
        if (!isset($_SERVER['SERVER_NAME'])) {
            $_SERVER['SERVER_NAME'] = 'localhost';
        }

        // Init PrestaShop.
        $this->initPrestaShop();
    }

    /**
     * Returns the single class instance, creating one if not yet existing.
     */
    public static function instance(): AcumulusTestsBootstrap
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Returns the root of the webshop.
     *
     * As our code is often symlinked nto test environments, using dirname with a given
     * level will not work, so we need to try to get the root in another way, the
     * --bootstrap option might help here.
     */
    private function getRoot(): string
    {
        $root = dirname(__FILE__, 5);
        // if our plugin is symlinked, we need to redefine root. Try to
        // find it by looking at the --bootstrap option as passed to phpunit.
        global $argv;
        if (is_array($argv)) {
            $i = array_search('--bootstrap', $argv, true);
            // if we found --bootstrap, the value is in the next entry.
            if (is_int($i) && count($argv) > $i + 1) {
                $bootstrapFile = $argv[$i + 1];
                $root = substr($bootstrapFile, 0, strpos($bootstrapFile, 'modules') - 1);
            }
        }
        return $root;
    }

    private function initPrestaShop(): void
    {
        // Initialize PrestaShop environment
        define('_PS_MODE_DEV_', true);

        // Include necessary PrestaShop files
        $root = $this->getRoot();
        /** @noinspection UntrustedInclusionInspection  false positive */
        require_once("$root/config/config.inc.php");

        // Create PrestaShop object and initialize Context (where necessary).
        $this->prestashop = new Shop();
        // Error: Cannot access protected property Shop::$context
        //$this->prestashop->context = Context::getContext();
        // Exception: Trying to get property 'precision' of non-object in
        //   classes\Context.php 557:  ...($this->currency->precision);
        Context::getContext()->currency = Currency::getDefaultCurrency();

    }
}

AcumulusTestsBootstrap::instance();
