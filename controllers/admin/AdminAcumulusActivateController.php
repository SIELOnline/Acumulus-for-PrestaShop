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

declare(strict_types=1);

include_once(__DIR__ . '/BaseAdminAcumulusController.php');

/**
 * AdminAcumulusActivateController provides the "Activate pro-support" form.
 */
class AdminAcumulusActivateController extends BaseAdminAcumulusController
{
    public function __construct()
    {
        $this->formType = 'activate';
        $this->icon = 'process-icon- icon-check';
        parent::__construct();
    }
}