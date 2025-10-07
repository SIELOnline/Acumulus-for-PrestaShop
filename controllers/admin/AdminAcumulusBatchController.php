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
 * AdminAcumulusBatchController provides the send batch form.
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
