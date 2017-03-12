<?php
/**
 * DO NOT USE the keywords namespace and use here! PrestaShop loads and eval()'s this code, leading to E_WARNINGs...
 *
 * @author    Buro RaDer / SIEL Acumulus
 * @copyright 2016 Buro RaDer
 * @license   see license.txt
 */

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
    public static $module_version = '4.7.2';

    /** @var array */
    protected $options = array();

    /** @var \Siel\Acumulus\Shop\Config */
    protected $acumulusConfig = null;

    /** @var \Siel\Acumulus\Shop\ConfigStoreInterface */
    protected $configStore;

    /** @var string */
    protected $confirmUninstall;

    public function __construct()
    {
        $this->name = 'acumulus';
        $this->tab = 'billing_invoicing';
        $this->version = self::$module_version;
        $this->author = 'Acumulus';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.9');
        $this->dependencies = array();
        $this->bootstrap = true;
        $this->module_key = '89693e3902e3d283a89fde3673dd3513';

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
    protected function t($key)
    {
        return $this->acumulusConfig->getTranslator()->get($key);
    }

    /**
     * Initializes the properties
     */
    protected function init()
    {
        if ($this->acumulusConfig === null) {
            // Load autoloader
            require_once(__DIR__ . '/libraries/Siel/psr4.php');

            $languageCode = isset(Context::getContext()->language) ? Context::getContext()->language->iso_code : 'nl';
            $this->acumulusConfig = new \Siel\Acumulus\Shop\Config('PrestaShop', $languageCode);
            $this->acumulusConfig->getTranslator()->add(new \Siel\Acumulus\Shop\ModuleTranslations());

            $this->displayName = $this->t('module_name');
            $this->description = $this->t('module_description');
        }
    }

    /**
     * @return \Siel\Acumulus\Shop\Config
     */
    public function getAcumulusConfig()
    {
        $this->init();
        return $this->acumulusConfig;
    }

    /**
     * Install module.
     *
     * @return bool
     */
    public function install()
    {
        $this->init();
        return $this->checkRequirements()
        && parent::install()
        && $this->createTables()
        && $this->installTabs()
        && $this->registerHook('actionOrderHistoryAddAfter')
        && $this->registerHook('actionOrderSlipAdd');
    }

    /**
     * Uninstall module.
     *
     * @return bool
     */
    public function uninstall()
    {
        $this->init();
        $this->confirmUninstall = $this->t('message_uninstall');

        // Delete our config values
        foreach ($this->acumulusConfig->getKeys() as $key) {
            Configuration::deleteByName("ACUMULUS_$key");
        }
        $this->dropTables();
        $this->uninstallTabs();

        return parent::uninstall();
    }

    /**
     * Checks the requirements for this module (CURL, DOMXML, ...).
     *
     * @return bool
     *   Success.
     */
    protected function checkRequirements()
    {
        $requirements = new \Siel\Acumulus\Helpers\Requirements();
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
     * Adds menu-items for the batch and advanced config forms.
     *
     * - Proudly copied from gamification.
     * - Public so it can be called by update functions.
     *
     * @return bool
     */
    public function installTabs()
    {
        $this->init();
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminAcumulusBatch';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Acumulus';
        }
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminParentOrders');
        $tab->module = $this->name;
        $tab->position = 1001;
        $result1 = (bool) $tab->add();

        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminAcumulusAdvanced';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $this->t('advanced_page_title');
        }
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminTools');
        $tab->module = $this->name;
        $tab->position = 1001;
        $result2 = (bool) $tab->add();

        return $result1 && $result2;
    }

    /**
     * Removes menu-items for the batch and advanced config forms.
     *
     * - Proudly copied from gamification.
     * - Public so it can be called by update functions.
     *
     * @return bool
     */
    public function uninstallTabs()
    {
        $id_tab = (int) Tab::getIdFromClassName('AdminAcumulusBatch');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            $result1 = $tab->delete();
        } else {
            $result1 = false;
        }

        $id_tab = (int) Tab::getIdFromClassName('AdminAcumulusAdvanced');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            $result2 = $tab->delete();
        } else {
            $result2 = false;
        }

        return $result1 && $result2;
    }

    /**
     * Renders the configuration form.
     *
     * @return string
     */
    public function getContent()
    {
        $this->init();

        // Add some styling in PS 1.5.
        if (version_compare(_PS_VERSION_, 1.6, '<')) {
            $this->context->controller->addCSS($this->_path . 'views/css/config-form.css');
        }

        $form = $this->acumulusConfig->getForm('config');
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
    protected function processForm(Siel\Acumulus\Helpers\Form $form)
    {
        $output = '';
        $form->process();
        foreach ($form->getErrorMessages() as $message) {
            $output .= $this->displayError($message);
        }
        foreach ($form->getWarningMessages() as $message) {
            $output .= $this->displayWarning($message);
        }
        foreach ($form->getSuccessMessages() as $message) {
            $output .= $this->displayConfirmation($message);
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
    protected function renderForm(Siel\Acumulus\Helpers\Form $form)
    {
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

        // Title and toolbar.
        $helper->title = $this->displayName;
        $helper->show_toolbar = true; // false -> remove toolbar
        $helper->toolbar_scroll = true; // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit' . $this->name;

        // This seems to be for PS 1.5 and lower only ... doesn't work in 1.6.
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->t('button_save'),
                'href' => $currentIndex . '&configure=' . $this->name . '&save' . $this->name . '&token=' . $adminTokenLite,
            ),
            'back' => array(
                'href' => $currentIndex . '&token=' . $adminTokenLite,
                'desc' => $this->t('button_back'),
            ),
        );

        /** @noinspection PhpUndefinedFieldInspection */
        $helper->multiple_fieldsets = true;
        $formMapper = new \Siel\Acumulus\PrestaShop\Helpers\FormMapper();
        $fields_form = $formMapper->map($form);
        $fields_form['formSubmit']['form'] = array(
            'legend' => array(
                'title' => $this->t('button_save'),
                'icon' => 'icon-save',
            ),
            'submit' => array(
              'title' => $this->t('button_save'),
            )
        );
        $helper->show_cancel_button = true;
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
     */
    public function hookactionOrderHistoryAddAfter(array $params)
    {
        $this->init();
        $type = \Siel\Acumulus\PrestaShop\Invoice\Source::Order;
        $source = new \Siel\Acumulus\PrestaShop\Invoice\Source($type, $params['order_history']->id_order);
        $this->acumulusConfig->getManager()->sourceStatusChange($source);
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
     */
    public function hookactionOrderSlipAdd(array $params)
    {
        $this->init();
        /** @var Order $order */
        $order = $params['order'];
        $orderSlips = $order->getOrderSlipsCollection();
        /** @var OrderSLip $newestOrderSlip */
        $newestOrderSlip = null;
        foreach ($orderSlips as $orderSlip) {
            /** @var OrderSlip $orderSlip */
            if ($newestOrderSlip === null || $orderSlip->date_add > $newestOrderSlip->date_add) {
                $newestOrderSlip = $orderSlip;
            }
        }
        $type = \Siel\Acumulus\PrestaShop\Invoice\Source::CreditNote;
        $source = new \Siel\Acumulus\PrestaShop\Invoice\Source($type, $newestOrderSlip);
        $this->acumulusConfig->getManager()->sourceStatusChange($source);
        return true;
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
        return $this->acumulusConfig->getAcumulusEntryModel()->install();
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
        return $this->acumulusConfig->getAcumulusEntryModel()->uninstall();
    }
}
