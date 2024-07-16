<?php
/**
 * See http://doc.prestashop.com/display/PS15/Auto-updating+modules
 * and ModuleCore::runUpgradeModule().
 *
 * @author    Buro RaDer, https://burorader.com/
 * @copyright SIEL BV, https://www.siel.nl/acumulus/
 * @license   GPL v3, see license.txt
 *
 * @noinspection PhpUnused
 */

/**
 * param Acumulus $object
 *
 * @return bool
 *
 * @throws \Exception
 *
 * @noinspection PhpRedundantCatchClauseInspection
 */
function upgrade_module_8_2_0(/*Acumulus $object*/): bool
{
    // Drop and recreate index (to make it non-unique). Already done in 6.0.0 and 8.0.0,
    // but both times with errors (in 6.0.0 the create statement was not adapted, so users
    //  who started using this module after 6.0.0, and before 8.0.0, would still get a
    //  unique index. In 8.0.0 with a syntax error in the create part)
    $tableName = _DB_PREFIX_ . 'acumulus_entry';
    try {
        $resultDrop = Db::getInstance()->execute("ALTER TABLE `$tableName` DROP INDEX `acumulus_idx_entry_id`");
    } catch (PrestaShopException $e) {
        $resultDrop = false;
    }
    try {
        $resultCreate = Db::getInstance()->execute("CREATE INDEX `acumulus_idx_entry_id` ON `$tableName` (`entry_id`)");
    } catch (PrestaShopException $e) {
        $resultCreate = false;
    }
    return $resultCreate;
}
