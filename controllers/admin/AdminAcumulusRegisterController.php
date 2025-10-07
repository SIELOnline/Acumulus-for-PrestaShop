<?php
/**
 * @noinspection PhpUnused
 * @noinspection LongInheritanceChainInspection  long inheritance chain is in PS
 *
 * Validator says: Missing short description in file comment.
 *
 * @author    Buro RaDer, https://burorader.com/
 * @copyright SIEL BV, https://www.siel.nl/acumulus/
 * @license   GPL v3, see license.txt
 */

declare(strict_types=1);

include_once(__DIR__ . '/BaseAdminAcumulusController.php');

/**
 * AdminAcumulusRegisterController provides the register form.
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
