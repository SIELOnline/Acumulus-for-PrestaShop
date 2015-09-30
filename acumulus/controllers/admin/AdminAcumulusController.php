<?php
/*
 * @file
 * Contains the Acumulus Controller class that provides additional features.
 * Proudly copied from AdminPreferencesController.
 */
use Siel\Acumulus\PrestaShop\Helpers\FormMapper;
use Siel\Acumulus\PrestaShop\Shop\BatchForm;

/**
 * Class AdminAcumulusController provides the send batch form feature.
 */
class AdminAcumulusController extends AdminController
{

  /** @var \Siel\Acumulus\Shop\Config */
  protected $acumulusConfig;

  /** @var \Siel\Acumulus\Web\Service */
  protected $webAPI;

  /** @var Acumulus */
  protected $module = null;

  /** @var \Siel\Acumulus\PrestaShop\Shop\BatchForm */
  protected $form;

  public function __construct() {
    $this->className = '';
    $this->table = '';
    $this->display = 'add';
    $this->bootstrap = true;

    // Initialization.
    $this->initAcumulus();

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
    return $this->module->getTranslator()->get($key);
  }

  /**
   * Initializes the properties
   */
  protected function initAcumulus() {
    if (!$this->module) {
      require_once(dirname(__FILE__) . '/../../acumulus.php');
      $this->module = new Acumulus();
      $this->module->init();
      $this->form = new BatchForm($this->module->getTranslator(), $this->module->getAcumulusConfig()->getManager());
    }
  }

  public function initToolbarTitle()
  {
    parent::initToolbarTitle();

    switch ($this->display)
    {
      case 'add':
        $this->toolbar_title[] = $this->t('batch_form_title');
        break;
    }
  }

  /**
   * Overridden to make it accessible by the form class
   *
   * @param string $msg
   */
  public function displayWarning($msg) {
    parent::displayWarning($msg);
  }

  /**
   * Overridden to make it accessible by the form class
   *
   * @param string $msg
   */
  public function displayInformation($msg) {
    parent::displayInformation($msg);
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
    // @todo: review this when Form has been refactored.
    $this->fields_value = $this->getForm()->getFormValues();
    return $this->fields_value;
  }

  /**
   *
   * @return \Siel\Acumulus\Shop\BatchForm
   *
   */
  protected function getForm() {
    return $this->form;
  }

}
