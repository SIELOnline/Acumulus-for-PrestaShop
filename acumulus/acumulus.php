<?php
/**
 * @file
 * Contains the Acumulus class, the base of our PrestaShop module.
 *
 * More information for non-PrestaShop developers that might have to maintain
 * this module's code can be found on the PrestaShop documentation site:
 *
 * http://doc.prestashop.com/display/PS16/Creating+a+PrestaShop+module
 *
 * Do not use the keywords namespace and use here, as on occasion PrestaShop
 * loads and eval()'s this code, leading to E_WARNINGs...
 */

if (!defined('_PS_VERSION_')) {
  exit;
}

/**
 * Class Acumulus defines a PrestaShop module that can interact with the
 * Acumulus webAPI to a.o. automatically send invoices to Acumulus.
 */
class Acumulus extends Module {
  /**
   * Increase this value on each change:
   * - point release: bug fixes
   * - minor version: addition of minor features, backwards compatible
   * - major version: major or backwards incompatible changes
   *
   * @var string
   */
  public static $module_version = '4.0.0-alpha1';

  /** @var array */
  protected $options = array();

  /** @var Siel\Acumulus\PrestaShop\PrestaShopAcumulusConfig */
  protected $acumulusConfig;

  /** @var Siel\Acumulus\Common\WebAPI */
  protected $webAPI;

  public function __construct() {
    $this->name = 'acumulus';
    $this->tab = 'billing_invoicing';
    $this->version = static::$module_version;
    $this->author = 'Acumulus';
    $this->need_instance = 0;
    $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.9');
    $this->dependencies = array();

    parent::__construct();

    // Initialization.
    $this->init();

    $this->displayName = $this->acumulusConfig->t('module_name');
    $this->description = $this->acumulusConfig->t('module_description');
    $this->confirmUninstall = $this->acumulusConfig->t('message_uninstall');
  }

  /**
   * Initializes the properties
   */
  protected function init() {
    if (!$this->acumulusConfig) {
      require_once(dirname(__FILE__) . '/Siel/Acumulus/Common/TranslatorInterface.php');
      require_once(dirname(__FILE__) . '/Siel/Acumulus/Common/BaseTranslator.php');
      require_once(dirname(__FILE__) . '/Siel/Acumulus/Common/ConfigInterface.php');
      require_once(dirname(__FILE__) . '/Siel/Acumulus/Common/BaseConfig.php');
      require_once(dirname(__FILE__) . '/Siel/Acumulus/Common/WebAPICommunication.php');
      require_once(dirname(__FILE__) . '/Siel/Acumulus/Common/WebAPI.php');
      require_once(dirname(__FILE__) . '/Siel/Acumulus/PrestaShop/PrestaShopAcumulusConfig.php');

      $language = isset(Context::getContext()->language) ? Context::getContext()->language->iso_code : 'nl';
      $this->acumulusConfig = new Siel\Acumulus\PrestaShop\PrestaShopAcumulusConfig($language);
      $this->webAPI = new Siel\Acumulus\Common\WebAPI($this->acumulusConfig);

      // Requirements checking. Not sure if this is the right place.
      foreach ($this->webAPI->checkRequirements() as $error) {
        $this->warning = $this->acumulusConfig->t($error['message']);
      }
    }
  }

  /**
   * Install module.
   *
   * @return bool
   */
  public function install() {
    if (!parent::install()
      || !$this->registerHook('actionOrderHistoryAddAfter')
      || !$this->createTables()
      || !$this->installTab()
    ) {
      return false;
    }
    return true;
  }

  /**
   * Uninstall module.
   *
   * @return bool
   */
  public function uninstall() {
    // Delete our config values
    foreach ($this->acumulusConfig->getKeys() as $key) {
      Configuration::deleteByName("ACUMULUS_$key");
    }
    $this->dropTables();
    $this->uninstallTab();
    return parent::uninstall();
  }

  /**
   * Adds a menu-item: proudly copied from gamification.
   */
  public function installTab() {
    $tab = new Tab();
    $tab->active = 1;
    $tab->class_name = 'AdminAcumulus';
    $tab->name = array();
    foreach (Language::getLanguages(true) as $lang) {
      $tab->name[$lang['id_lang']] = 'Acumulus';
    }
    $tab->id_parent = (int) Tab::getIdFromClassName('AdminParentOrders');
    $tab->module = $this->name;
    $tab->position = 1001;
    return $tab->add();
  }

  public function uninstallTab() {
    $id_tab = (int) Tab::getIdFromClassName('AdminAcumulus');
    if ($id_tab) {
      $tab = new Tab($id_tab);
      return $tab->delete();
    }
    else {
      return false;
    }
  }

  /**
   * Renders the configuration page.
   *
   * @return string
   */
  public function getContent() {
    $this->context->controller->addCSS($this->_path . 'config-form.css');
    require_once(dirname(__FILE__) . '/Siel/Acumulus/PrestaShop/AcumulusConfigForm.php');
    $form = new Siel\Acumulus\PrestaShop\AcumulusConfigForm($this->acumulusConfig, $this);
    return $form->getContent();
  }

  /**
   * Hook actionOrderHistoryAddAfter.
   *
   * @param array $params
   *
   * @return bool
   */
  public function hookactionOrderHistoryAddAfter(array $params) {
    $this->init();
    if ($params['order_history']->id_order_state == $this->acumulusConfig->get('triggerOrderStatus')) {
      // We cannot know if an admin, a user or a batch triggered this hook.
      // So we assume the process to be not interactive and will send a mail
      // upon failure.
      return $this->sendOrderToAcumulus(new Order($params['order_history']->id_order));
    }
    return true;
  }

  /**
   * @param Order $order
   *   The order to send to Acumulus.
   *
   * @return bool
   *   Success.
   */
  public function sendOrderToAcumulus(Order $order) {
    // Do not use a use statement as that leads to a:
    // [PrestaShop] Fatal error in module Module.php(1080) : eval()'d : syntax error, unexpected 'use' (T_USE)
    // (In PS 1.6 it gives a different error, but still an error.)
    $this->webAPI = new \Siel\Acumulus\Common\WebAPI($this->acumulusConfig);
    require_once(dirname(__FILE__) . '/Siel/Acumulus/PrestaShop/InvoiceAdd.php');

    $addInvoice = new Siel\Acumulus\PrestaShop\InvoiceAdd($this->acumulusConfig, $this);
    $invoice = $addInvoice->convertOrderToAcumulusInvoice($order);
    Hook::exec('actionAcumulusInvoiceAdd', array('invoice' => &$invoice, 'order' => $order), null, true);
    $result = $this->webAPI->invoiceAdd($invoice, $order->id);

    // Store entry id and token.
    if (!empty($result['invoice'])) {
      $this->saveAcumulusEntry($result['invoice'], $order);
    }

    // Send a mail if there are messages.
    $messages = $this->webAPI->resultToMessages($result);
    if (!empty($messages)) {
      $this->sendMail($result, $messages, $order);
    }

    return !empty($result['invoice']['invoicenumber']);
  }

  /**
   * @param array $invoice
   * @param Order $order
   *
   * @return bool
   */
  public function saveAcumulusEntry(array $invoice, Order $order) {
    $model = $this->getAcumulusEntryModel();
    return $model->save($invoice, $order);
  }

  /**
   * @param array$result
   * @param array $messages
   * @param Order $order
   */
  protected function sendMail(array $result, array $messages, Order $order) {
    $id_lang = Context::getContext()->language->id;
    $mailDir = dirname(__FILE__) . '/mails/';
    $template_name = 'messages';
    $title = $this->acumulusConfig->t('mail_subject');
    $templateVars = array(
      '{order_id}' => $order->id,
      '{invoice_id}' => isset($result['invoice']['invoicenumber']) ? $result['invoice']['invoicenumber'] : $this->acumulusConfig->t('message_no_invoice'),
      '{status}' => $result['status'],
      '{status_text}' => $this->webAPI->getStatusText($result['status']),
      '{status_1_text}' => $this->webAPI->getStatusText(1),
      '{status_2_text}' => $this->webAPI->getStatusText(2),
      '{status_3_text}' => $this->webAPI->getStatusText(3),
      '{messages}' => $this->webAPI->messagesToText($messages),
      '{messages_html}' => htmlspecialchars($this->webAPI->messagesToHtml($messages), ENT_NOQUOTES),
    );
    $credentials = $this->acumulusConfig->getCredentials();
    $toEmail = !empty($credentials['emailonerror']) ? $credentials['emailonerror'] : Configuration::get('PS_SHOP_EMAIL');
    $toName = Configuration::get('PS_SHOP_NAME');
    $from = Configuration::get('PS_SHOP_EMAIL');
    $fromName = Configuration::get('PS_SHOP_NAME');

    Mail::Send($id_lang, $template_name, $title, $templateVars, $toEmail, $toName, $from, $fromName, NULL, NULL, $mailDir);
  }

  /**
   * Creates the tables this module uses. Called during install or update.
   *
   * Actual creation is done by the models.
   *
   * @return bool
   */
  public function createTables() {
    $model = $this->getAcumulusEntryModel();
    return $model->install();
  }

  /**
   * Drops the tables this module uses. Called during uninstall.
   *
   * Actual creation is done by the models.
   *
   * @return bool
   */
  public function dropTables() {
    $model = $this->getAcumulusEntryModel();
    return $model->uninstall();
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
   *   Will receive a log of results, 1 entry per order keyed by order-id.
   *
   * @return bool true on success, false otherwise.
   * true on success, false otherwise.
   */
  public function sendOrders($orderIds, $forceSend, array &$log) {
    $success = true;
    $entryModel = $this->getAcumulusEntryModel();
    $time_limit = ini_get('max_execution_time');
    foreach ($orderIds as $orderId) {
      // Try to keep the script running, but note that other systems involved,
      // think the (Apache) web server, may have their own time-out.
      set_time_limit($time_limit);
      if ($forceSend || !$entryModel->getByOrderId($orderId)) {
        $order = new Order($orderId);
        if (!empty($order->id) && $order->id == $orderId) {
          if ($this->sendOrderToAcumulus($order)) {
            $log[$orderId] = sprintf($this->acumulusConfig->t('message_batch_send_1_success'), $orderId);
          }
          else {
            $log[$orderId] = sprintf($this->acumulusConfig->t('message_batch_send_1_error'), $orderId);
            $success       = FALSE;
          }
        }
        else {
          $log[$orderId] = sprintf($this->acumulusConfig->t('message_batch_send_1_not_found'), $orderId);
        }
      }
      else {
        $log[$orderId] = sprintf($this->acumulusConfig->t('message_batch_send_1_skipped'), $orderId);
      }
    }
    return $success;
  }

  /**
   * @return \Siel\Acumulus\PrestaShop\AcumulusEntry
   */
  protected function getAcumulusEntryModel() {
    static $model = null;
    if ($model === null) {
      require_once(dirname(__FILE__) . '/Siel/Acumulus/PrestaShop/AcumulusEntry.php');
      $model = new Siel\Acumulus\PrestaShop\AcumulusEntry();
    }
    return $model;
  }

}
