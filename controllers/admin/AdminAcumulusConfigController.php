<?php
/**
 * @noinspection PhpUnused
 *
 * Validator says: Missing short description in file comment.
 *
 * @author    Buro RaDer, https://burorader.com/
 * @copyright SIEL BV, https://www.siel.nl/acumulus/
 * @license   GPL v3, see license.txt
 */

include_once('BaseAdminAcumulusController.php');

/**
 * AdminAcumulusConfigController provides the config form as a menu item.
 */
class AdminAcumulusConfigController extends BaseAdminAcumulusController
{
    public function __construct()
    {
        $this->formType = 'config';
        $this->icon = 'process-icon-save';
        parent::__construct();
    }
}
