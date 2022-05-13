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
function upgrade_module_6_4_0(Acumulus $object): bool
{
    $version = Db::getInstance()->getValue(
        sprintf('SELECT version FROM `%smodule` WHERE name = "%s"', _DB_PREFIX_, Db::getInstance()->escape($object->name)));
    return $object->getAcumulusContainer()->getConfigUpgrade()->upgrade($version);
}
