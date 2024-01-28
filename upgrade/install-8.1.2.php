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
function upgrade_module_8_1_2(Acumulus $object): bool
{
    // Recreate our tabs.
    return $object->installTabs();
}
