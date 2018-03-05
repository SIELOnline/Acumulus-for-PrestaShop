<?php
/**
 * DO NOT USE the keywords namespace and use here! PrestaShop loads and eval()'s
 * this code, leading to E_WARNINGs...
 *
 * @author    Buro RaDer, http://www.burorader.com/
 * @copyright SIEL BV, https://www.siel.nl/acumulus/
 * @license   GPL v3, see license.txt
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
    /** @var array */
    protected $options = array();

    /** @var \Siel\Acumulus\Helpers\Container */
    protected $container = null;

    /** @var string */
    protected $confirmUninstall;

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
        $this->version = '5.2.1';
        $this->name = 'acumulus';
        $this->tab = 'billing_invoicing';
        $this->author = 'Acumulus';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.9');
        $this->dependencies = array();
        /** @noinspection PhpUndefinedFieldInspection */
        $this->bootstrap = true;
        $this->module_key = 'bf7e535d7c51990bdbf70f00e1209521';

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

            /** @noinspection PhpUndefinedClassInspection */
            $languageCode = isset(Context::getContext()->language) ? Context::getContext()->language->iso_code : 'nl';
            $this->container = new \Siel\Acumulus\Helpers\Container('PrestaShop', $languageCode);
            $this->container->getTranslator()->add(new \Siel\Acumulus\Shop\ModuleTranslations());

            $this->displayName = $this->t('module_name');
            $this->description = $this->t('module_description');
        }
    }

    /**
     * @return \Siel\Acumulus\Helpers\ContainerInterface
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
          && parent::install()
          && $this->createTables();
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall()
    {
        $this->init();
        $this->confirmUninstall = $this->t('message_uninstall');

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
            && $this->installTabs()
            && $this->registerHook('actionOrderHistoryAddAfter')
            && $this->registerHook('actionOrderSlipAdd');
    }

    /**
     * {@inheritdoc}
     */
    public function disable($force_all = false)
    {
        parent::disable($force_all);
        return $this->unregisterHook('actionOrderHistoryAddAfter')
            && $this->unregisterHook('actionOrderSlipAdd')
            && $this->uninstallTabs();
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
        /** @noinspection PhpUnhandledExceptionInspection */
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminAcumulusBatch';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Acumulus';
        }

//      /** @var \PrestaShopBundle\Entity\Repository\TabRepository $tabRepository */
//      $tabRepository = this->context->controller->get('prestashop.core.admin.tab.repository');
//      $tab->id_parent = $tabRepository->findOneIdByClassName('AdminParentOrders');
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminParentOrders');
        $tab->module = $this->name;
        $tab->position = 1001;
        $result1 = (bool) $tab->add();

        /** @noinspection PhpUnhandledExceptionInspection */
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminAcumulusAdvanced';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $this->t('advanced_page_title');
        }
        // Tab 'AdminAdvancedParameters' exists as of 1.7, check result.
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminAdvancedParameters');
        if ($tab->id_parent === 0) {
            $tab->id_parent = (int) Tab::getIdFromClassName('AdminTools');
        }
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
            /** @noinspection PhpUnhandledExceptionInspection */
            $tab = new Tab($id_tab);
            /** @noinspection PhpUnhandledExceptionInspection */
            $result1 = $tab->delete();
        } else {
            $result1 = false;
        }

        $id_tab = (int) Tab::getIdFromClassName('AdminAcumulusAdvanced');
        if ($id_tab) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $tab = new Tab($id_tab);
            /** @noinspection PhpUnhandledExceptionInspection */
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
        // Add some styling in PS 1.5.
        if (version_compare(_PS_VERSION_, 1.6, '<')) {
            $this->context->controller->addCSS($this->_path . 'views/css/config-form.css');
        }

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

        /** @noinspection PhpUndefinedClassInspection */
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
                'href' => $helper->currentIndex . '&save' . $this->name . '&token=' . $adminTokenLite,
            ),
            'back' => array(
                'href' => $currentIndex . '&token=' . $adminTokenLite,
                'desc' => $this->t('button_back'),
            ),
        );

        /** @noinspection PhpUndefinedFieldInspection */
        $helper->multiple_fieldsets = true;
        $formMapper = $this->getAcumulusContainer()->getFormMapper();
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
        $this->getAcumulusContainer()->getManager()->sourceStatusChange($source);
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
        /** @var OrderSlip $newestOrderSlip */
        $newestOrderSlip = null;
        foreach ($orderSlips as $orderSlip) {
            /** @var OrderSlip $orderSlip */
            if ($newestOrderSlip === null || $orderSlip->date_add > $newestOrderSlip->date_add) {
                $newestOrderSlip = $orderSlip;
            }
        }
        $type = \Siel\Acumulus\PrestaShop\Invoice\Source::CreditNote;
        $source = new \Siel\Acumulus\PrestaShop\Invoice\Source($type, $newestOrderSlip);
        $this->getAcumulusContainer()->getManager()->sourceStatusChange($source);
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
