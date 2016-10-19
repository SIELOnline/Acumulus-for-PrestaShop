<?php
/**
 * Validator says: Missing short description in file comment.
 *
 * @author    Buro RaDer / SIEL Acumulus
 * @copyright 2016 Buro RaDer
 * @license   see license.txt
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
        parent::__construct();
        $this->title = $this->t('button_send');
        $this->icon = 'process-icon-partial_refund';
    }
}
