<?php
/**
 * See http://doc.prestashop.com/display/PS15/Auto-updating+modules and ModuleCore::runUpgradeModule().
 *
 * @author    Buro RaDer / SIEL Acumulus
 * @copyright 2016 Buro RaDer
 * @license   see license.txt
 */

/**
 * @param Acumulus $object
 *
 * @return bool
 */
function upgrade_module_3_3_3(/** @noinspection PhpUnusedParameterInspection */ $object)
{
    $tableName = _DB_PREFIX_ . 'acumulus_entry';
    return Db::getInstance()->execute(
        "CREATE TABLE IF NOT EXISTS `$tableName` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `id_shop` INTEGER UNSIGNED NOT NULL DEFAULT '1',
        `id_shop_group` INTEGER UNSIGNED NOT NULL DEFAULT '1',
        `id_entry` int(11) NOT NULL,
        `token` char(32) NOT NULL,
        `id_order` int(11) NOT NULL,
        `created` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated` timestamp NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE INDEX `idx_entry_id` (`id_entry`),
        UNIQUE INDEX `idx_order_id` (`id_order`))"
    );
}
