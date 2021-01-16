<?php
/**
 * @noinspection PhpUndefinedClassInspection
 * @noinspection PhpMissingParamTypeInspection
 * @noinspection PhpMissingReturnTypeInspection
 *
 * @author    Buro RaDer, https://burorader.com/
 * @copyright SIEL BV, https://www.siel.nl/acumulus/
 * @license   GPL v3, see license.txt
 */

use Siel\Acumulus\Helpers\Container;
use Siel\Acumulus\Helpers\Form;
use Siel\Acumulus\Helpers\Message;
use Siel\Acumulus\Helpers\Severity;
use Siel\Acumulus\Invoice\Source;
use Siel\Acumulus\Shop\BatchFormTranslations;
use Siel\Acumulus\Shop\ConfigFormTranslations;
use Siel\Acumulus\Shop\RegisterFormTranslations;
use Siel\Acumulus\Shop\InvoiceStatusForm;
use Siel\Acumulus\Shop\InvoiceStatusFormTranslations;

/**
 * Acumulus defines a PrestaShop module that can interact with the Acumulus
 * webAPI to send invoices to Acumulus.
 *
 * More information for non-PrestaShop developers that might have to maintain
 * this module's code can be found on the PrestaShop documentation site:
 * http://doc.prestashop.com/display/PS16/Creating+a+PrestaShop+module
 *
 */
class Acumulus extends Module
{
    /** @var array */
    protected $options = array();

    /** @var \Siel\Acumulus\Helpers\Container */
    protected $container = null;

    /** @var string */
    protected $confirmUninstallMsg;

    public function __construct()
    {
        /**
         * Increase this value on each change:
         * - point release: bug fixes
         * - minor version: addition of minor features, backwards compatible
         * - major version: major or backwards incompatible changes
         *
         * PrestaShop Note: maximum version length = 8, so do not use alpha or beta.
         *
         * @var string
         */
        $this->version = '6.1.2';
        $this->name = 'acumulus';
        $this->tab = 'billing_invoicing';
        $this->author = 'Acumulus';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.9');
        $this->dependencies = array();
        $this->bootstrap = true;
        $this->module_key = 'bf7e535d7c51990bdbf70f00e1209521';

        parent::__construct();

        $this->displayName = $this->l('Acumulus');
        $this->description = $this->l('Module that sends invoice data for your orders and refunds to your online Acumulus administration.');
        $this->confirmUninstallMsg = $this->l('Are you sure you want to uninstall?');
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
    protected function t($key)
    {
        return $this->container->getTranslator()->get($key);
    }

    /**
     * Initializes the properties
     */
    protected function init()
    {
        if ($this->container === null) {
            // Load autoloader
            require_once(dirname(__FILE__) . '/lib/siel/acumulus/SielAcumulusAutoloader.php');
            SielAcumulusAutoloader::register();

            $languageCode = isset(Context::getContext()->language) ? Context::getContext()->language->iso_code : 'nl';
            $this->container = new Container('PrestaShop', $languageCode);

            $this->displayName = $this->t('module_name');
            $this->description = $this->t('module_description');
        }
    }

    /**
     * @return \Siel\Acumulus\Helpers\Container
     */
    public function getAcumulusContainer()
    {
        $this->init();
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        $this->init();
        return $this->checkRequirements()
          and parent::install()
          and $this->createTables();
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall()
    {
        $this->init();
        $this->confirmUninstallMsg = $this->t('message_uninstall');

        // Delete our config values
        foreach ($this->getAcumulusContainer()->getConfig()->getKeys() as $key) {
            Configuration::deleteByName("ACUMULUS_$key");
        }
        $this->dropTables();

        return parent::uninstall();
    }

    /**
     * {@inheritdoc}
     */
    public function enable($force_all = false)
    {
        return parent::enable($force_all)
            and $this->installTabs()
            and $this->registerHooks();
    }

    /**
     * {@inheritdoc}
     */
    public function disable($force_all = false)
    {
        return parent::disable($force_all)
               and $this->unregisterHooks()
               and $this->uninstallTabs();
    }

    /**
     * Checks the requirements for this module (CURL, DOMXML, ...).
     *
     * @return bool
     *   Success.
     */
    protected function checkRequirements()
    {
        $requirements = $this->container->getRequirements();
        $messages = $requirements->check();
        foreach ($messages as $key => $message) {
            $translatedMessage = $this->t($key);
            if ($translatedMessage === $key) {
                $translatedMessage = $message;
            }
            $this->displayError($translatedMessage);
        }
        return empty($this->messages);
    }

    /**
     * Enables the hooks that this module wants to respond to.
     *
     * @return bool
     */
    public function registerHooks()
    {
        return $this->registerHook('actionOrderHistoryAddAfter')
               and $this->registerHook('actionOrderSlipAdd')
               and $this->registerHook('displayAdminOrderLeft');
    }

    /**
     * Disables the hooks that this module wants to respond to.
     *
     * @return bool
     */
    public function unregisterHooks()
    {
        return $this->registerHook('actionOrderHistoryAddAfter')
               and $this->registerHook('actionOrderSlipAdd')
               and $this->registerHook('displayAdminOrderLeft');
    }

    /**
     * Adds menu-items.
     *
     * - Proudly copied from gamification.
     * - Public so it can be called by update functions.
     *
     * @return bool
     */
    public function installTabs()
    {

        $this->init();

        // Add the batch form.
        $this->container->getTranslator()->add(new BatchFormTranslations());
        $tab = new Tab();
        $tab->active = true;
        $tab->class_name = 'AdminAcumulusBatch';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $this->t('batch_form_header');
        }
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminParentOrders');
        $tab->module = $this->name;
        $tab->position = 1001;
        $result1 = (bool) $tab->add();

        // Add the advanced config form.
        $this->container->getTranslator()->add(new ConfigFormTranslations());
        $tab = new Tab();
        $tab->active = true;
        $tab->class_name = 'AdminAcumulusAdvanced';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $this->t('advanced_form_header');
        }
        // Tab 'AdminAdvancedParameters' exists as of 1.7, check result.
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminAdvancedParameters');
        if ($tab->id_parent === 0) {
            $tab->id_parent = (int) Tab::getIdFromClassName('AdminTools');
        }
        $tab->module = $this->name;
        $tab->position = 1001;
        $result2 = (bool) $tab->add();

        // Add the register form.
        $this->container->getTranslator()->add(new RegisterFormTranslations());
        $tab = new Tab();
        $tab->active = false;
        $tab->class_name = 'AdminAcumulusRegister';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $this->t('register_form_header');
        }
        // Tab 'AdminAdvancedParameters' exists as of 1.7, check result.
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminAdvancedParameters');
        if ($tab->id_parent === 0) {
            $tab->id_parent = (int) Tab::getIdFromClassName('AdminTools');
        }
        $tab->module = $this->name;
        $tab->position = 1002;
        $result3 = (bool) $tab->add();

        // Add the invoice form.
        $this->container->getTranslator()->add(new InvoiceStatusFormTranslations());
        $tab = new Tab();
        $tab->active = false;
        $tab->class_name = 'AdminAcumulusInvoice';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $this->t('invoice_form_header');
        }
        // Tab 'AdminAdvancedParameters' exists as of 1.7, check result.
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminAdvancedParameters');
        if ($tab->id_parent === 0) {
            $tab->id_parent = (int) Tab::getIdFromClassName('AdminTools');
        }
        $tab->module = $this->name;
        $tab->position = 1003;
        $result4 = (bool) $tab->add();

        return $result1 && $result2 && $result3 && $result4;
    }

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * Removes menu-items.
     *
     * - Proudly copied from gamification.
     * - Public so it can be called by update functions.
     * - Returns true as to not worry users about messages that PS could not
     *   deactivate this module.
     *
     * @return bool
     */
    public function uninstallTabs()
    {
        $id_tab = (int) Tab::getIdFromClassName('AdminAcumulusBatch');
        if ($id_tab) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $tab = new Tab($id_tab);
            /** @noinspection PhpUnhandledExceptionInspection */
            $tab->delete();
        }

        $id_tab = (int) Tab::getIdFromClassName('AdminAcumulusAdvanced');
        if ($id_tab) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $tab = new Tab($id_tab);
            /** @noinspection PhpUnhandledExceptionInspection */
            $tab->delete();
        }

        $id_tab = (int) Tab::getIdFromClassName('AdminAcumulusRegister');
        if ($id_tab) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $tab = new Tab($id_tab);
            /** @noinspection PhpUnhandledExceptionInspection */
            $tab->delete();
        }

        $id_tab = (int) Tab::getIdFromClassName('AdminAcumulusInvoice');
        if ($id_tab) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $tab = new Tab($id_tab);
            /** @noinspection PhpUnhandledExceptionInspection */
            $tab->delete();
        }

        return true;
    }

    /**
     * Renders the configuration form.
     *
     * @return string
     */
    public function getContent()
    {
        $form = $this->getAcumulusContainer()->getForm('config');
        $output = '';
        $output .= $this->processForm($form);
        $output .= $this->renderForm($form);
        return $output;
    }

    /**
     * Processes the form (if it was submitted).
     *
     * @param \Siel\Acumulus\Helpers\Form $form
     *
     * @return string
     *   Any output from the processing stage that has to be rendered: messages.
     */
    protected function processForm(Form $form)
    {
        $output = '';
        $form->process();
        // Force the creation of the fields to get connection error messages
        // shown.
        $form->getFields();
        foreach ($form->getMessages(Severity::RealMessages) as $message) {
            switch ($message->getSeverity()) {
                case Severity::Success:
                    $output .= $this->displayConfirmation($message->format(Message::Format_PlainWithSeverity));
                    break;
                case Severity::Info:
                case Severity::Notice:
                    $output .= $this->displayInformation($message->format(Message::Format_PlainWithSeverity));
                    break;
                case Severity::Warning:
                    $output .= $this->displayWarning($message->format(Message::Format_PlainWithSeverity));
                    break;
                case Severity::Error:
                case Severity::Exception:
                    $output .= $this->displayError($message->format(Message::Format_PlainWithSeverity));
                    break;
                default:
                    break;
            }
        }
        return $output;
    }

    /**
     * Renders the HTML for the form.
     *
     * @param \Siel\Acumulus\Helpers\Form $form
     *
     * @return string
     *   The rendered form HTML.
     */
    protected function renderForm(Form $form)
    {
        $this->context->controller->addCSS($this->_path . 'views/css/acumulus.css');
        $this->context->controller->addJS($this->_path . 'views/js/acumulus.js');

        // Create and initialize form helper.
        $helper = new HelperForm();

        $adminTokenLite = Tools::getAdminTokenLite('AdminModules');
        $currentIndex = AdminController::$currentIndex;

        // Module, token and currentIndex.
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = $adminTokenLite;
        $helper->currentIndex = $currentIndex . '&configure=' . $this->name;

        // Language.
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        $helper->title = $this->displayName;
        /** @noinspection PhpUndefinedFieldInspection */
        $helper->multiple_fieldsets = true;
        $formMapper = $this->getAcumulusContainer()->getFormMapper();
        $fields_form = $formMapper->map($form);

        if ($form->isFullPage()) {
            // Title and toolbar.
            $helper->show_toolbar = true; // false -> remove toolbar
            $helper->toolbar_scroll = true; // yes - > Toolbar is always visible on the top of the screen.
            $helper->submit_action = 'submit' . $this->name;

            $fields_form['formSubmit']['form'] = array(
                'legend' => array(
                    'title' => $this->t("button_submit_{$form->getType()}"),
                    'icon' => 'icon-save',
                ),
                'submit' => array(
                    'title' => $this->t("button_submit_{$form->getType()}"),
                )
            );
            $helper->show_cancel_button = true;
        } else {
            $helper->show_toolbar = false; // false -> remove toolbar
            $helper->show_cancel_button = false;
            $helper->multiple_fieldsets = true;
        }
        $helper->tpl_vars = array(
            'fields_value' => $form->getFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm($fields_form);
    }

    /**
     * Hook actionOrderHistoryAddAfter.
     *
     * @param array $params
     *   Array with the following entries:
     *   - order_history: OrderHistory
     *
     * @return bool
     *
     * @noinspection PhpUnused
     */
    public function hookactionOrderHistoryAddAfter(array $params)
    {
        $this->init();
        $source = $this->container->getSource(Source::Order, $params['order_history']->id_order);
        $this->getAcumulusContainer()->getInvoiceManager()->sourceStatusChange($source);
        return true;
    }

    /**
     * Hook actionOrderSlipAdd.
     *
     * @param array $params
     *   Array with the following entries:
     *   - order: Order
     *   - productList: array
     *   - qtyList: array
     *
     * @return bool
     *
     * @noinspection PhpUnused
     */
    public function hookactionOrderSlipAdd(array $params)
    {
        $this->init();
        /** @var Order $order */
        $order = $params['order'];
        $orderSlips = $order->getOrderSlipsCollection();
        /** @var OrderSlip $newestOrderSlip */
        $newestOrderSlip = null;
        foreach ($orderSlips as $orderSlip) {
            /** @var OrderSlip $orderSlip */
            if ($newestOrderSlip === null || $orderSlip->date_add > $newestOrderSlip->date_add) {
                $newestOrderSlip = $orderSlip;
            }
        }
        $source = $this->container->getSource(Source::CreditNote, $newestOrderSlip);
        $this->getAcumulusContainer()->getInvoiceManager()->sourceStatusChange($source);
        return true;
    }

    /**
     * Hook displayAdminOrderLeft.
     *
     * @param array $params
     *   Array with the following entries:
     *   - id_order: Order id
     *
     * @return string
     *   The html we want to be output on the order details screen.
     *
     * @noinspection PhpUnused
     */
    public function hookDisplayAdminOrderLeft(array $params)
    {
        $this->init();
        if ($this->getAcumulusContainer()->getConfig()->getInvoiceStatusSettings()['showInvoiceStatus']) {
            $this->context->controller->addCSS($this->_path . 'views/css/acumulus.css');
            $this->context->controller->addJS($this->_path . 'views/js/acumulus-ajax.js');

            // Create form to already load form translations and to set the Source.
            /** @var \Siel\Acumulus\Shop\InvoiceStatusForm $form */
            $form = $this->getAcumulusContainer()->getForm('invoice');
            $orderId = $params['id_order'];
            $source = $this->container->getSource(Source::Order, $orderId);
            $form->setSource($source);

            return $this->renderFormInvoice($form);
        }
        return '';
    }

    /**
     * Renders the form.
     *
     * This method is called by eithe hookDisplayAdminOrderLeft() or by the
     * AdminAcumulusInvoiceController and should return the rendered form.
     *
     * @param \Siel\Acumulus\Shop\InvoiceStatusForm $form
     *
     * @return string
     *   The rendered form.
     */
    public function renderFormInvoice(InvoiceStatusForm $form)
    {
        $id = 'acumulus-' . $form->getType();
        $url = $this->getAcumulusContainer()->getShopCapabilities()->getLink('invoice');
        $wait = $this->t('wait');
        $formRenderer = $this->getAcumulusContainer()->getFormRenderer();
        $output = '';
        $output .= "<form method='POST' action='$url' id='$id' class='form-horizontal acumulus-area' data-acumulus-wait='$wait'>";
        $output .= $formRenderer->render($form);
        $output .= '</form>';
        return $output;
    }

    /**
     * Creates the tables this module uses. Called during install() or update
     * (install-4.0.2.php).
     *
     * Actual creation is done by the models. This method might get called via an
     * install or update script: make it public and call init().
     *
     * @return bool
     */
    public function createTables()
    {
        $this->init();
        return $this->getAcumulusContainer()->getAcumulusEntryManager()->install();
    }

    /**
     * Drops the tables this module uses. Called during uninstall.
     *
     * Actual creation is done by the models.
     *
     * @return bool
     */
    protected function dropTables()
    {
        return $this->getAcumulusContainer()->getAcumulusEntryManager()->uninstall();
    }
}
