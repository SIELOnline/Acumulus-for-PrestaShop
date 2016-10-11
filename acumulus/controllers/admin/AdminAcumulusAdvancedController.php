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
 * AdminAcumulusAdvancedController provides the advanced settings form feature.
 */
class AdminAcumulusAdvancedController extends BaseAdminAcumulusController
{
    public function __construct()
    {
        $this->formType = 'advanced';
        parent::__construct();
        $this->title = $this->t('button_save');
        $this->icon = 'process-icon-save';
    }
}
