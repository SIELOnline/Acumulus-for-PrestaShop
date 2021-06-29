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
function upgrade_module_6_3_0($object)
{
    $result = $object->getAcumulusContainer()->getConfig()->upgrade('6.2.9');
    return $result;
}
