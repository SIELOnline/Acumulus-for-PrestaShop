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
 */
function upgrade_module_8_0_0(/*Acumulus $object*/): bool
{
    // Drop and recreate index (to make it non-unique). (Already done in 6.0.0, but
    // the create statement was not adapted, so users who started using this module
    // after 6.0.0, and before 8.0.0, will still get a unique index.)
    $tableName = _DB_PREFIX_ . 'acumulus_entry';
    return Db::getInstance()->execute("ALTER TABLE `$tableName` DROP INDEX `acumulus_idx_entry_id`")
           and Db::getInstance()->execute("CREATE INDEX `acumulus_idx_entry_id` ON `tableName` (`entry_id`)");
}
