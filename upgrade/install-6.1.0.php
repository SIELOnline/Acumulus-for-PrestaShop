<?php
/**
 * See http://doc.prestashop.com/display/PS15/Auto-updating+modules and
 * ModuleCore::runUpgradeModule().
 *
 * @author    Buro RaDer, https://burorader.com/
 * @copyright SIEL BV, https://www.siel.nl/acumulus/
 * @license   GPL v3, see license.txt
 */

/**
 * @param Acumulus $object
 *
 * @return bool
 */
function upgrade_module_6_1_0($object)
{
    // Update tabs, ignore errors on uninstallTabs() as that removes not yet
    // registered tabs.
    $result1 = $object->uninstallTabs();
    $result2 = $object->installTabs();
    return $result2;
}
