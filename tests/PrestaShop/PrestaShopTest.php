<?php
/**
 * @noinspection PhpStaticAsDynamicMethodCallInspection
 */

declare(strict_types=1);

namespace Siel\Acumulus\Tests\PrestaShop;

use Context;
use Db;
use PHPUnit\Framework\TestCase;
use PrestaShop;
use Shop;

use function define;
use function dirname;

/**
 * PrestaShopTest is a base class for PrestaShop Acumulus integration tests.
 */
class PrestaShopTest extends TestCase
{
    private Shop $prestashop;

    protected function setUp(): void
    {
        // Set up PrestaShop environment
        $this->initPrestaShop();
    }

    protected function tearDown(): void
    {
        // Clean up PrestaShop environment
        $this->cleanupPrestaShop();
    }

    public function testExample(): void
    {
        // Your test logic using $this->prestashop
        $this->assertTrue(true);
    }

    /**
     * @noinspection UntrustedInclusionInspection
     */
    private function initPrestaShop(): void
    {
        // Initialize PrestaShop environment
        define('_PS_MODE_DEV_', true);

        // Include necessary PrestaShop files
        $root = dirname(__FILE__, 5);
        require_once("$root/config/config.inc.php");
        require_once("$root/init.php");

        // Set up a testing database (replace with your testing database details)
        $dbServer = 'localhost';
        $dbUser = 'your_db_user';
        $dbPassword = 'your_db_password';
        $dbName = 'your_test_database';

        define('_DB_SERVER_', $dbServer);
        define('_DB_USER_', $dbUser);
        define('_DB_PASSWD_', $dbPassword);
        define('_DB_NAME_', $dbName);

        // Create PrestaShop object
        $this->prestashop = new Shop();
        $this->prestashop->context = Context::getContext();
    }

    private function cleanupPrestaShop(): void
    {
        // Clean up PrestaShop environment (e.g., close database connections)
        Db::getInstance()->disconnect();
    }
}
