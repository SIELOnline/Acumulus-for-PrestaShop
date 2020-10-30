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
 * AdminAcumulusRegisterController provides the register form feature.
 */
class AdminAcumulusRegisterController extends BaseAdminAcumulusController
{
    public function __construct()
    {
        $this->formType = 'register';
        $this->icon = 'process-icon- icon-user-plus';
        parent::__construct();
    }
}
