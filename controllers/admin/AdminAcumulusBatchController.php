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
 * AdminAcumulusBatchController provides the send batch form feature.
 */
class AdminAcumulusBatchController extends BaseAdminAcumulusController
{
    public function __construct()
    {
        $this->formType = 'batch';
        $this->icon = 'process-icon-partial_refund';
        parent::__construct();
    }
}
