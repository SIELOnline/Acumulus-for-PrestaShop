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
 * DO NOT USE the keywords namespace and use here, as on occasion PrestaShop
 * loads and eval()'s this code, leading to E_WARNINGs...
 */
if (!defined('_PS_VERSION_')) {
  exit;
}

/**
 * Acumulus defines a PrestaShop module that can interact with the
 * Acumulus webAPI to send invoices to Acumulus.
 *
 * @property string $confirmUninstall
 */
class Acumulus extends Module {

  /**
   * Increase this value on each change:
   * - point release: bug fixes
   * - minor version: addition of minor features, backwards compatible
   * - major version: major or backwards incompatible changes
   *
   * Note: maximum version length = 8.
   *
   * @var string
   */
  public static $module_version = '4.0.0-a1';

  /** @var array */
  protected $options = array();

  /** @var \Siel\Acumulus\Helpers\TranslatorInterface */
  protected $translator;

  /** @var \Siel\Acumulus\Shop\Config */
  protected $acumulusConfig;

  /** @var \Siel\Acumulus\Shop\ConfigStoreInterface */
  protected $configStore;

  public function __construct() {
    $this->name = 'acumulus';
    $this->tab = 'billing_invoicing';
    $this->version = static::$module_version;
    $this->author = 'Acumulus';
    $this->need_instance = 0;
    $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.9');
    $this->dependencies = array();
    $this->bootstrap = TRUE;

    parent::__construct();

    // This object can get created quite often: try to prevent initialization.
    // (in fact: as we don't attach tour module name to the tab, it does not
    // get created anymore that often, but we still keep this code.
    if ($_GET['controller'] === 'AdminModules') {
      $this->init();
    }
  }

  /**
   * Initializes the properties
   */
  public function init() {
    if ($this->translator === NULL) {
      // Load autoloader
      require_once(dirname(__FILE__) . '/libraries/Siel/psr4.php');

      $languageCode = isset(Context::getContext()->language) ? Context::getContext()->language->iso_code : 'nl';
      \Siel\Acumulus\PrestaShop\Helpers\Log::createInstance();
      $this->translator = new \Siel\Acumulus\Helpers\Translator($languageCode);
      $translations = new \Siel\Acumulus\Shop\ModuleTranslations();
      $this->translator->add($translations);

      $this->displayName = $this->translator->get('module_name');
      $this->description = $this->translator->get('module_description');

      $this->acumulusConfig = new \Siel\Acumulus\Shop\Config(new \Siel\Acumulus\PrestaShop\Shop\ConfigStore(), $this->translator);
    }
  }

  /**
   * @return \Siel\Acumulus\Helpers\TranslatorInterface
   */
  public function getTranslator() {
    return $this->translator;
  }

  /**
   * @return \Siel\Acumulus\Shop\Config
   */
  public function getAcumulusConfig() {
    return $this->acumulusConfig;
  }


  /**
   * Install module.
   *
   * @return bool
   */
  public function install() {
    $this->init();
    return $this->checkRequirements()
    && parent::install()
    && $this->createTables()
    && $this->installTab()
    && $this->registerHook('actionOrderHistoryAddAfter');
  }

  /**
   * Uninstall module.
   *
   * @return bool
   */
  public function uninstall() {
    $this->init();
    $this->confirmUninstall = $this->translator->get('message_uninstall');

    // Delete our config values
    foreach ($this->acumulusConfig->getKeys() as $key) {
      Configuration::deleteByName("ACUMULUS_$key");
    }
    $this->dropTables();
    $this->uninstallTab();

    return parent::uninstall();
  }

  /**
   * Checks the requirements for this module (CURL, DOMXML, ...).
   *
   * @return bool
   *   Success.
   */
  public function checkRequirements() {
    $this->init();
    $requirements = new \Siel\Acumulus\Helpers\Requirements();
    $messages = $requirements->check();
    foreach ($messages as $key => $message) {
      $translatedMessage = $this->translator->get($key);
      if ($translatedMessage === $key) {
        $translatedMessage = $message;
      }
      $this->displayError($translatedMessage);
    }
    return empty($this->messages);
  }

  /**
   * Adds a menu-item: proudly copied from gamification.
   */
  public function installTab() {
    $tab = new Tab();
    $tab->active = 1;
    $tab->class_name = 'AdminAcumulus';
    $tab->name = array();
    foreach (Language::getLanguages(TRUE) as $lang) {
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
      return FALSE;
    }
  }

  /**
   * Renders the configuration form.
   *
   * @return string
   */
  public function getContent() {
    $this->init();

    // Add some styling in PS 1.5.
    if (version_compare(_PS_VERSION_, 1.6, '<')) {
      $this->context->controller->addCSS($this->_path . 'config-form.css');
    }

    $form = new \Siel\Acumulus\PrestaShop\Shop\ConfigForm($this->translator, $this->acumulusConfig, $this->name);
    $output = '';
    $output .= $this->processForm($form);
    $output .= $this->renderForm($form);
    return $output;
  }

  /**
   * Processes the form (if it was submitted).
   *
   * @param \Siel\Acumulus\PrestaShop\Shop\ConfigForm $form
   *
   * @return string
   *   Any output from the processing stage that has to be rendered: error or
   *   success messages.
   */
  protected function processForm(Siel\Acumulus\PrestaShop\Shop\ConfigForm $form) {
    $output = '';
    $form->process();
    foreach ($form->getErrorMessages() as $message) {
      $output .= $this->displayError($message);
    }
    foreach ($form->getSuccessMessages() as $message) {
      $output .= $this->displayConfirmation($message);
    }
    return $output;
  }

  /**
   * Renders the HTML for the form.
   *
   * @param \Siel\Acumulus\PrestaShop\Shop\ConfigForm $form
   *
   * @return string
   *   The rendered form HTML.
   */
  protected function renderForm(Siel\Acumulus\PrestaShop\Shop\ConfigForm $form) {
    // Create and initialize form helper.
    $helper = new HelperForm();

    // Module, token and currentIndex.
    $helper->module = $this;
    $helper->name_controller = $this->name;
    $helper->token = Tools::getAdminTokenLite('AdminModules');
    $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

    // Language.
    $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
    $helper->default_form_language = $default_lang;
    $helper->allow_employee_form_lang = $default_lang;

    // Title and toolbar.
    $helper->title = $this->displayName;
    $helper->show_toolbar = TRUE; // false -> remove toolbar
    $helper->toolbar_scroll = TRUE; // yes - > Toolbar is always visible on the top of the screen.
    $helper->submit_action = 'submit' . $this->name;
    $helper->toolbar_btn = array(
      'save' => array(
        'desc' => $this->translator->get('button_save'),
        'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'),
      ),
      'back' => array(
        'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
        'desc' => $this->translator->get('button_back')
      )
    );

    $helper->multiple_fieldsets = TRUE;
    $formMapper = new \Siel\Acumulus\PrestaShop\Helpers\FormMapper();
    $fields_form = $formMapper->map($form);
    end($fields_form);
    $lastFieldsetKey = key($fields_form);
    $fields_form[$lastFieldsetKey]['form']['submit'] = array(
      'title' => $this->translator->get('button_save'),
    );
    $helper->show_cancel_button = TRUE;
    $helper->tpl_vars = array(
      // @todo: review this when Form has been refactored.
      'fields_value' => $form->getFormValues(),
      'languages' => $this->context->controller->getLanguages(),
      'id_language' => $this->context->language->id
    );
    return $helper->generateForm($fields_form);
  }

  /**
   * Hook actionOrderHistoryAddAfter.
   *
   * @param array $params
   *
   * @return bool
   */
  public function hookactionOrderHistoryAddAfter(array $params) {
    // @todo: check how to handle refunds (OrderSlip): upon creation? do they trigger this hook at all?
    $this->init();
    $order = new Order($params['order_history']->id_order);
    $type = \Siel\Acumulus\PrestaShop\Invoice\Source::Order;
    $source = new \Siel\Acumulus\PrestaShop\Invoice\Source($type, $order);
    $this->acumulusConfig->getManager()->sourceStatusChange($source, $params['order_history']->id_order_state);
    return TRUE;
  }

  /**
   * Creates the tables this module uses. Called during install or update.
   *
   * Actual creation is done by the models. This method might get called via an
   * install or update script: make it public and call init().
   *
   * @return bool
   */
  public function createTables() {
    //
    $this->init();
    return $this->acumulusConfig->getAcumulusEntryModel()->install();
  }

  /**
   * Drops the tables this module uses. Called during uninstall.
   *
   * Actual creation is done by the models.
   *
   * @return bool
   */
  protected function dropTables() {
    return $this->acumulusConfig->getAcumulusEntryModel()->uninstall();
  }

}
