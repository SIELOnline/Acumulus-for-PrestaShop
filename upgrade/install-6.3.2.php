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
function upgrade_module_6_3_2(Acumulus $object): bool
{
    // Update hooks, ignore errors on unregisterHooks() as that removes
    // non-registered hooks.
    $object->unregisterHooks();
    return $object->registerHooks();
}
