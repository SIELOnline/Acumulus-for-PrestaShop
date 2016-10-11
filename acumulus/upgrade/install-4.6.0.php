<?php
/**
 * See http://doc.prestashop.com/display/PS15/Auto-updating+modules and
 * ModuleCore::runUpgradeModule().
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
function upgrade_module_4_6_0($object)
{
    // Remove tab with old name.
    $id_tab = (int) Tab::getIdFromClassName('AdminAcumulus');
    if ($id_tab) {
        $tab = new Tab($id_tab);
        $result1 = $tab->delete();
    } else {
        // Ignore: probably already removed.
        $result1 = true;
    }

    // Install new tabs.
    $result2 = $object->installTabs();
    return $result1 && $result2;
}
