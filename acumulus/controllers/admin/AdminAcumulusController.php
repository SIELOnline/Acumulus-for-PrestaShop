<?php
if (!defined('_PS_VERSION_')) {
  exit;
}

/**
 * @file
 * Contains the Acumulus Controller class that provides additional features.
 * Proudly copied from AdminPreferencesController.
 *
 * @license see license.txt
 */
use Siel\Acumulus\PrestaShop\Helpers\FormMapper;

/**
 * Class AdminAcumulusController provides the send batch form feature.
 */
class AdminAcumulusController extends AdminController
{
  /** @var Acumulus */
  protected $module = null;

  public function __construct() {
    $this->className = '';
    $this->table = '';
    $this->display = 'add';
    $this->bootstrap = true;

    // Initialization.
    require_once(dirname(__FILE__) . '/../../acumulus.php');
    $this->module = new Acumulus();

    parent::__construct();
  }

  /**
   * Helper method to translate strings.
   *
   * @param string $key
   *  The key to get a translation for.
   *
   * @return string
   *   The translation for the given key or the key itself if no translation
   *   could be found.
   */
  protected function t($key) {
    return $this->module->getAcumulusConfig()->getTranslator()->get($key);
  }


  /**
   * @return \Siel\Acumulus\Shop\BatchForm
   */
  protected function getForm() {
    return $this->module->getAcumulusConfig()->getForm('batch');
  }

  public function initToolbarTitle() {
    parent::initToolbarTitle();

    switch ($this->display)
    {
      case 'add':
        $this->toolbar_title[] = $this->t('batch_form_title');
        break;
    }
  }

  /**
   * Renders the form.
   *
   * @return string
   *   The rendered form.
   */
  public function renderForm() {
    $this->show_form_cancel_button = true;
    $this->multiple_fieldsets = true;
    $form = $this->getForm();
    $formMapper = new FormMapper();
    $fields_form = $formMapper->map($form);
    reset($fields_form);
    $firstFieldsetKey = key($fields_form);
    $fields_form[$firstFieldsetKey]['form']['submit'] = array(
      'title' => $this->t('button_send'),
      'icon' => 'process-icon-envelope',
    );
    $this->fields_form = $fields_form;

    return parent::renderForm();
  }

  /**
   * Processes the form (if it was submitted).
   */
  public function processSave() {
    $form = $this->getForm();
    $form->process();
    foreach ($form->getErrorMessages() as $message) {
      $this->displayWarning($message);
    }
    foreach ($form->getSuccessMessages() as $message) {
      $this->displayInformation($message);
    }
    $this->display = 'add';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsValue($obj) {
    parent::getFieldsValue($obj);
    $this->fields_value = $this->getForm()->getFormValues();
    return $this->fields_value;
  }

}
