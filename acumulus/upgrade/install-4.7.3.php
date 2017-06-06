<?php
/**
 * See http://doc.prestashop.com/display/PS15/Auto-updating+modules and
 * ModuleCore::runUpgradeModule().
 *
 * @author    Buro RaDer / SIEL Acumulus
 * @copyright 2017 Buro RaDer
 * @license   see license.txt
 */

/**
 * @param Acumulus $object
 *
 * @return bool
 */
function upgrade_module_4_7_3($object)
{
    return $object->getAcumulusContainer()->getConfig()->upgrade('4.7.3');
}
