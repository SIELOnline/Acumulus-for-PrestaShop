<?php
/**
 * See http://doc.prestashop.com/display/PS15/Auto-updating+modules and
 * ModuleCore::runUpgradeModule().
 *
 * @author    Buro RaDer, https://burorader.com/
 * @copyright SIEL BV, https://www.siel.nl/acumulus/
 * @license   GPL v3, see license.txt
 *
 * @noinspection PhpUnused
 */

/**
 * @param Acumulus $object
 *
 * @return bool
 */
function upgrade_module_7_3_0(Acumulus $object): bool
{
    // Update tab, ignore errors on uninstallTabs().
    $object->uninstallTabs();
    return $object->installTabs();
}
