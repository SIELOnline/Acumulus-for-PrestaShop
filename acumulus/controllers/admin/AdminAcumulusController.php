<?php
/*
 * @file
 * Contains the Acumulus Controller class that provides additional features.
 * Proudly copied from AdminPreferencesController.
 */

class AdminAcumulusController extends AdminController
{

  /** @var Siel\Acumulus\PrestaShop\PrestaShopAcumulusConfig */
  protected $acumulusConfig;

  /** @var Siel\Acumulus\Common\WebAPI */
  protected $webAPI;

  /** @var Acumulus */
  protected $module;

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
   * Initializes the properties
   */
  protected function initAcumulus() {
    if (!$this->acumulusConfig) {
      require_once(dirname(__FILE__) . '/../../acumulus.php');
      $path = dirname(__FILE__) . '/../../Siel/Acumulus/';
      require_once($path . 'Common/TranslatorInterface.php');
      require_once($path . 'Common/BaseTranslator.php');
      require_once($path . 'Common/ConfigInterface.php');
      require_once($path . 'Common/BaseConfig.php');
      require_once($path . 'Common/WebAPICommunication.php');
      require_once($path . 'Common/WebAPI.php');
      require_once($path . 'PrestaShop/PrestaShopAcumulusConfig.php');

      $language = isset(Context::getContext()->language) ? Context::getContext()->language->iso_code : 'nl';
      $this->acumulusConfig = new Siel\Acumulus\PrestaShop\PrestaShopAcumulusConfig($language);
      $this->webAPI = new Siel\Acumulus\Common\WebAPI($this->acumulusConfig);
      $this->module = new Acumulus();
    }
  }

  public function initToolbarTitle()
  {
    parent::initToolbarTitle();

    switch ($this->display)
    {
      case 'add':
        $this->toolbar_title[] = $this->acumulusConfig->t('batchSendTitle');
        break;
    }
  }

  /**
   * Overridden to make it accessible by the form class
   */
  public function displayWarning($msg) {
    parent::displayWarning($msg);
  }

  /**
   * Overridden to make it accessible by the form class
   */
  public function displayInformation($msg) {
    parent::displayInformation($msg);
  }

  public function renderForm() {
    $this->show_form_cancel_button = true;

    $form = $this->getForm();
    $this->fields_form = $form->getFormFields();

    return parent::renderForm();
  }

  public function processSave() {
    $form = $this->getForm();
    if ($form->isSubmitted()) {
      $form->processSubmit();
    }
    $this->display = 'add';
  }

  public function getFieldsValue($obj) {
    parent::getFieldsValue($obj);
    $form = $this->getForm();
    $form->getFieldValues($this->fields_value);
    return $this->fields_value;
  }


  /**
   * Send a collection of orders to Acumulus.
   *
   * @param array $orderIds
   *  The collection of order ids to send.
   * @param bool $forceSend
   *   Whether invoices that already have been sent to Acumulus, should be send
   *   again.
   * @param array $log
   *
   * @return bool
   *   true on success, false otherwise.
   */
  public function sendOrders($orderIds, $forceSend, array &$log) {
    return $this->module->sendOrders($orderIds, $forceSend, $log);
  }

  /**
   *
   * @return \Siel\Acumulus\PrestaShop\AcumulusSendForm
   *
   */
  protected function getForm() {
    static $form = null;
    if ($form === null) {
      require_once(dirname(__FILE__) . '/../../Siel/Acumulus/PrestaShop/AcumulusSendForm.php');
      $form = new Siel\Acumulus\PrestaShop\AcumulusSendForm($this, $this->acumulusConfig);
    }
    return $form;
  }

}
