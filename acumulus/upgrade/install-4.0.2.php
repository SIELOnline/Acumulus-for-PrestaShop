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
function upgrade_module_4_0_2($object)
{
    $tableName = _DB_PREFIX_ . 'acumulus_entry';
    $oldTableName = $tableName . '_old';

    // Rename current table.
    $result = Db::getInstance()->execute("ALTER TABLE `$tableName` RENAME `$oldTableName`;");

    // Create new table.
    $result = $object->createTables() && $result;

    // Copy data from old to new table.
    // Orders only, credit slips were not supported in that version.
    $insertOrders = <<<SQL
insert into $tableName
(id_shop, id_shop_group, id_entry, token, source_type, source_id, created, updated)
select id_shop, id_shop_group, id_entry, token, 'Order' as source_type, id_order as source_id, created, updated
from $oldTableName;
SQL;
    $result = Db::getInstance()->execute($insertOrders) && $result;

    // Delete old table.
    $result = Db::getInstance()->execute("DROP TABLE `$oldTableName`") && $result;
    return $result;
}
