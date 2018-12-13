<?php
/**
 * See http://doc.prestashop.com/display/PS15/Auto-updating+modules
 * and ModuleCore::runUpgradeModule().
 *
 * @author    Buro RaDer, https://burorader.com/
 * @copyright SIEL BV, https://www.siel.nl/acumulus/
 * @license   GPL v3, see license.txt
 */

/**
 * param Acumulus $object
 *
 * @return bool
 */
function upgrade_module_4_4_0()
{
    $tableName = _DB_PREFIX_ . 'acumulus_entry';
    return Db::getInstance()->execute(
        "ALTER TABLE `$tableName`
        CHANGE COLUMN `id_entry` `id_entry` INT(11) NULL DEFAULT NULL,
        CHANGE COLUMN `token` `token` CHAR(32) NULL DEFAULT NULL"
    );
}
