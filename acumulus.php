<?php
/**
 * @author    Buro RaDer, https://burorader.com/
 * @copyright SIEL BV, https://www.siel.nl/acumulus/
 * @license   GPL v3, see license.txt
 */

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use PrestaShop\PrestaShop\Core\Exception\ContainerNotFoundException;
use PrestaShop\PrestaShop\Core\Version;
use Siel\Acumulus\Config\Config;
use Siel\Acumulus\Helpers\Container;
use Siel\Acumulus\Helpers\Form;
use Siel\Acumulus\Helpers\Message;
use Siel\Acumulus\Helpers\MessageCollection;
use Siel\Acumulus\Helpers\Severity;
use Siel\Acumulus\Invoice\Source;
use Siel\Acumulus\Shop\ActivateSupportFormTranslations;
use Siel\Acumulus\Shop\BatchFormTranslations;
use Siel\Acumulus\Shop\ConfigFormTranslations;
use Siel\Acumulus\Shop\RegisterFormTranslations;
use Siel\Acumulus\Shop\InvoiceStatusForm;
use Siel\Acumulus\Shop\InvoiceStatusFormTranslations;

use const Siel\Acumulus\Version;

/**
 * Acumulus defines a PrestaShop module that can interact with the Acumulus
 * webAPI to send invoices to Acumulus.
 *
 * More information for non-PrestaShop developers that might have to maintain
 * this module's code can be found on the PrestaShop documentation site:
 * http://doc.prestashop.com/display/PS16/Creating+a+PrestaShop+module
 *
 * @noinspection EfferentObjectCouplingInspection
 * @noinspection AutoloadingIssuesInspection
 */
class Acumulus extends Module
{
    protected Container $acumulusContainer;
    protected string $confirmUninstallMsg;

    public function __construct()
    {
        /**
         * PrestaShop Note: maximum version length = 8, so do not use alpha or beta.
         */
        $this->version = '8.2.0';
        $this->name = 'acumulus';
        $this->tab = 'billing_invoicing';
        $this->author = 'Acumulus';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => '9'];
        $this->dependencies = [];
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
    protected function t(string $key): string
    {
        return $this->getAcumulusContainer()->getTranslator()->get($key);
    }

    /**
     * Initializes the properties
     */
    protected function init(): void
    {
        if (!isset($this->acumulusContainer)) {
            // Load autoloader
            require_once __DIR__ . '/vendor/autoload.php';

            // Load our Container.
            $languageCode = isset(Context::getContext()->language) ? Context::getContext()->language->iso_code : 'nl';
            $this->acumulusContainer = new Container('PrestaShop', $languageCode);

            $this->displayName = $this->t('module_name');
            $this->description = $this->t('module_description');
        }
    }

    public function getAcumulusContainer(): Container
    {
        $this->init();
        return $this->acumulusContainer;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \JsonException
     */
    public function install(): bool
    {
        $this->init();
        return $this->checkRequirements()
          and parent::install()
          and $this->initConfig()
          and $this->createTables();
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(): bool
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
    public function enable($force_all = false): bool
    {
        return parent::enable($force_all)
            and $this->installTabs()
            and $this->registerHooks();
    }

    /**
     * {@inheritdoc}
     */
    public function disable($force_all = false): bool
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
    protected function checkRequirements(): bool
    {
        $requirements = $this->getAcumulusContainer()->getRequirements();
        $messages = $requirements->check();
        foreach ($messages as $key => $message) {
            $translatedMessage = $this->t($key);
            if ($translatedMessage === $key) {
                $translatedMessage = $message;
            }
            if (strpos($key, 'warning') !== false) {
                $this->adminDisplayWarning($translatedMessage);
            } else {
                $this->_errors[] = $translatedMessage;
            }
        }
        return empty($this->messages);
    }

    /**
     * Enables the hooks that this module wants to respond to.
     *
     * @return bool
     */
    public function registerHooks(): bool
    {
        $hooks = [
            'actionOrderHistoryAddAfter',
            'actionOrderSlipAdd',
            'actionAdminControllerSetMedia',
            'actionAdminMenuTabsModifier',
        ];
        if (version_compare($this->getVersion(), '1.7.7.0', '>=')) {
            $hooks = array_merge($hooks, [
                'displayAdminOrderTabLink',
                'displayAdminOrderTabContent',
            ]);
        } else {
            $hooks = array_merge($hooks, [
                'displayAdminOrderLeft',
            ]);
        }
        $result = true;
        foreach ($hooks as $hook) {
            $result = $this->registerHook($hook) && $result;
        }
        return $result;
    }

    /**
     * Disables the hooks that this module wanted to respond to.
     *
     * @return bool
     *   True, some hooks are bound to not be registered (1.7 vs 8.x), so we ignore the
     *   results of the calls to {@see \Module::unregisterHook()}.
     */
    public function unregisterHooks(): bool
    {
        $hooks = [
            'actionOrderHistoryAddAfter',
            'actionOrderSlipAdd',
            'actionAdminControllerSetMedia',
            'actionAdminMenuTabsModifier',
            'displaybackOfficeEmployeeMenu',
            'displayAdminOrderTabLink',
            'displayAdminOrderTabContent',
            'displayAdminOrderLeft',
            'actionGetAdminOrderButtons',
        ];
        foreach ($hooks as $hook) {
            $this->unregisterHook($hook);
        }
        return true;
    }

    /**
     * Adds menu-items.
     * - Proudly copied from gamification.
     * - Public so it can be called by update functions.
     *
     * @return bool
     *   Success.
     *
     * @noinspection PhpRedundantCatchClauseInspection
     * @noinspection DuplicatedCode
     */
    public function installTabs(): bool
    {
        try {
            $this->uninstallTabs();
            $tabs = $this->getAcumulusTabs();

            $result = true;
            foreach ($tabs as $tabInfo) {
                if ($tabInfo['translations'] !== null) {
                    $this->getAcumulusContainer()->getTranslator()->add(new $tabInfo['translations']());
                }
                $tab = new Tab();
                $tab->active = $tabInfo['active'];
                $tab->class_name = $tabInfo['className'];
                $tab->name = [];
                foreach (Language::getLanguages(true) as $lang) {
                    $tab->name[$lang['id_lang']] = $this->t($tabInfo['header']);
                }
                $tab->id_parent = $tabInfo['parent'];
                $tab->module = $this->name;
                $tab->position = $tabInfo['position'];
                try {
                    $result = $tab->add() && $result;
                } catch (PrestaShopException) {
                    $result = false;
                }
            }
        } catch (ContainerNotFoundException $e) {
            $this->getAcumulusContainer()->getLog()->exception($e);
            $result = false;
        }
        return $result;
    }

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
    public function uninstallTabs(): bool
    {
        $result = true;
        try {
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
            /** @var \PrestaShopBundle\Entity\Repository\TabRepository $tabRepository */
            $tabRepository = $entityManager->getRepository(\PrestaShopBundle\Entity\Tab::class);
            $tabs = $this->getAcumulusTabs();
            foreach ($tabs as $tabInfo) {
                $id_tab = $tabRepository->findOneIdByClassName($tabInfo['className']);
                if ($id_tab) {
                    $tab = new Tab($id_tab);
                    $result = $tab->delete() && $result;
                }
            }
        } catch (ContainerNotFoundException|PrestaShopException $e) {
            $this->getAcumulusContainer()->getLog()->exception($e);
            $result = false;
        }
        return $result;
    }

    /**
     * Returns the set of Acumulus tabs (menu-items).
     *
     * @throws \PrestaShop\PrestaShop\Core\Exception\ContainerNotFoundException
     */
    protected function getAcumulusTabs(): array
    {
        /** @var \PrestaShop\PrestaShop\Core\Foundation\Database\EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var \PrestaShopBundle\Entity\Repository\TabRepository $tabRepository */
        $tabRepository = $entityManager->getRepository(\PrestaShopBundle\Entity\Tab::class);
        $id_orders_parent = $tabRepository->findOneIdByClassName('AdminParentOrders');
        $id_settings_parent = $tabRepository->findOneIdByClassName('AdminAdvancedParameters');

        return [
            [
                'className' => 'AdminAcumulusBatch',
                'header' => 'batch_form_header',
                'active' => true,
                'parent' => $id_orders_parent,
                'position' => 1001,
                'translations' => BatchFormTranslations::class,
            ],
            [
                'className' => 'AdminAcumulusSettings',
                'header' => 'settings_form_header',
                'active' => true,
                'parent' => $id_settings_parent,
                'position' => 1005,
                'translations' => ConfigFormTranslations::class,
            ],
            [
                'className' => 'AdminAcumulusConfig',
                'header' => 'config_form_header',
                'active' => true,
                'parent' => $id_settings_parent,
                'position' => 1006,
                'translations' => null,
            ],
            [
                'className' => 'AdminAcumulusMappings',
                'header' => 'mappings_form_header',
                'active' => true,
                'parent' => $id_settings_parent,
                'position' => 1010,
                'translations' => null,
            ],
            [
                'className' => 'AdminAcumulusAdvanced',
                'header' => 'advanced_form_header',
                'active' => true,
                'parent' => $id_settings_parent,
                'position' => 1011,
                'translations' => null,
            ],
            [
                'className' => 'AdminAcumulusActivate',
                'header' => 'activate_form_header',
                'active' => true,
                'parent' => $id_settings_parent,
                'position' => 1015,
                'translations' => ActivateSupportFormTranslations::class,
            ],
            [
                'className' => 'AdminAcumulusRegister',
                'header' => 'register_form_header',
                'active' => true,
                'parent' => $id_settings_parent,
                'position' => 1016,
                'translations' => RegisterFormTranslations::class,
            ],
            [
                'className' => 'AdminAcumulusInvoice',
                'header' => 'invoice_form_header',
                'active' => false,
                'parent' => $id_settings_parent,
                'position' => 1019,
                'translations' => InvoiceStatusFormTranslations::class,
            ],
        ];
    }

    /**
     * Renders the configuration form.
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function getContent(): string
    {
        $form = $this->getAcumulusContainer()->getForm('settings');
        try {
            $form->process();
            $formHtml = $this->renderForm($form);
        } catch (Throwable $e) {
            // We handle our "own" exceptions but only when we can process them
            // as we want, i.e. show it as an error at the beginning of the
            // form. That's why we start catching only after we have a form, and
            // stop catching just before we render our messages.
            $formHtml = $formHtml ?? '';
            try {
                $crashReporter = $this->getAcumulusContainer()->getCrashReporter();
                $message = $crashReporter->logAndMail($e);
                $form->createAndAddMessage($message, Severity::Exception);
            } catch (Throwable) {
                // We do not know if we have informed the user per mail or
                // screen, so assume not, and rethrow the original exception.
                throw $e;
            }
        }
        $messagesHtml = $this->renderMessages($form);
        return $messagesHtml . $formHtml;
    }

    /**
     * Renders the HTML for the form.
     *
     * As a side effect, any needed css and js is added to the controller.
     *
     * @param \Siel\Acumulus\Helpers\Form $form
     *
     * @return string
     *   The rendered form HTML.
     */
    protected function renderForm(Form $form): string
    {
        $this->context->controller->addCSS($this->_path . 'views/css/acumulus.css');
        $this->context->controller->addJS($this->_path . 'views/js/acumulus.js');

        // Create and initialize form helper.
        $helper = new HelperForm();
        // Module, token and currentIndex.
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        // Language, title and multi-fieldset.
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->title = $this->displayName;

        $formMapper = $this->getAcumulusContainer()->getFormMapper();
        $fields_form = $formMapper->map($form);
        if ($form->isFullPage()) {
            // Title and toolbar.
            $helper->show_toolbar = true; // false -> remove toolbar
            $helper->toolbar_scroll = true; // yes - > Toolbar is always visible at the top of the screen.
            $helper->submit_action = 'submit' . $this->name;

            $fields_form['formSubmit']['form'] = [
                'legend' => [
                    'title' => $this->t("button_submit_{$form->getType()}"),
                    'icon' => 'icon-save',
                ],
                'submit' => [
                    'title' => $this->t("button_submit_{$form->getType()}"),
                ],
            ];
            $helper->show_cancel_button = true;
        } else {
            $helper->show_toolbar = false; // false -> remove toolbar
            $helper->show_cancel_button = false;
        }
        $helper->tpl_vars = [
            'fields_value' => $form->getFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];
        return $helper->generateForm($fields_form);
    }

    /**
     * Returns an HTML string with the messages rendered in PS style.
     */
    public function renderMessages(MessageCollection $messageCollection): string
    {
        $output = '';
        foreach ($messageCollection->getMessages(Severity::RealMessages) as $message) {
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
                    assert(false, 'Unknown severity ' . $message->getSeverity());
            }
        }
        return $output;
    }

    /**
     * Hook actionOrderHistoryAddAfter.
     *
     * @param array $params
     *   Array with the following entries:
     *   - order_history: OrderHistory
     *
     * @throws \Throwable
     *
     * @noinspection PhpUnused  hook
     */
    public function hookactionOrderHistoryAddAfter(array $params): void
    {
        $this->init();
        $this->sourceStatusChange(Source::Order, $params['order_history']->id_order);
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
     * @throws \Throwable
     *
     * @noinspection PhpUnused  hook
     */
    public function hookactionOrderSlipAdd(array $params): void
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
        $this->sourceStatusChange(Source::CreditNote, $newestOrderSlip);
    }

    /**
     * @param string $invoiceSourceType
     *   The type of the invoice source to create.
     * @param int|object|array $invoiceSourceOrId
     *   The invoice source itself or its id to create a
     *   \Siel\Acumulus\Invoice\Source instance for.
     *
     * @return void
     *
     * @throws \Throwable
     */
    private function sourceStatusChange(string $invoiceSourceType, $invoiceSourceOrId): void
    {
        try {
            $source = $this->getAcumulusContainer()->createSource($invoiceSourceType, $invoiceSourceOrId);
            $this->getAcumulusContainer()->getInvoiceManager()->sourceStatusChange($source);
        } catch (Throwable $e) {
            try {
                $crashReporter = $this->getAcumulusContainer()->getCrashReporter();
                // We do not know if we are on the admin side, so we should not
                // try to display the message returned by logAndMail().
                $crashReporter->logAndMail($e);
            } catch (Throwable) {
                // We do not know if we have informed the user per mail or
                // screen, so assume not, and rethrow the original exception.
                throw $e;
            }
        }
    }

    /**
     * Hook displayAdminOrderLeft. Deprecated in 1.7.7.0
     *
     * @param array $params
     *   Array with the following entries:
     *   - id_order: Order id
     *
     * @return string
     *   The HTML we want to be output on the order details screen.
     *
     * @noinspection PhpUnused  hook
     */
    public function hookDisplayAdminOrderLeft(array $params): string
    {
        return $this->hookDisplayAdminOrderTabContent($params);
    }

    /**
     * Hook actionAdminControllerSetMedia
     *
     * This hook gets called on the order overview and order detail page when we
     * can still add css and js. However, regardless the information we use,
     * context, values, or set of parameters, it is impossible to distinguish
     * which type of page is actually rendered.
     *
     * So, even if we only want to add our css and js to the order detail page,
     * the order overview page also gets our css and js loaded. It is as it is.
     *
     * @noinspection PhpUnused  hook
     */
    public function hookActionAdminControllerSetMedia(): void
    {
        $controller = Tools::getValue('controller');
        if ($controller === 'AdminOrders') {
            if (version_compare($this->getVersion(), '1.7.7.0', '>=')) {
                $this->context->controller->addCSS($this->_path . 'views/css/acumulus.css');
            } else {
                $this->context->controller->addCSS($this->_path . 'views/css/acumulus-176-.css');
            }
            $this->context->controller->addJS($this->_path . 'views/js/acumulus-ajax.js');
        }
    }

    /**
     * Hook actionAdminMenuTabsModifier
     *
     * This hook gets called on the back office pages and can be used to dynamically show/
     * hide menu-items. We do so for our configuration forms based on
     * {@see \Siel\Acumulus\Config\ShopCapabilities::usesNewCode()}.
     *
     * @param array $params
     *    Array with the following entries:
     *    - tabs: array with tabs, a tab being an array containing the definition of a tab.
     *
     * @noinspection PhpUnused  hook
     */
    public function hookActionAdminMenuTabsModifier(array $params): void
    {
        $this->adminMenuTabsModifier($params['tabs']);
    }

    protected function adminMenuTabsModifier(array &$tabs): void
    {
        $checkAccount = $this->getAcumulusContainer()->getCheckAccount()->doCheck();
        foreach ($tabs as &$tab) {
            if ($tab['class_name'] === 'AdminAcumulusActivate') {
                $tab['active'] = !empty($checkAccount);
            }
            if (!empty($tab['sub_tabs'])) {
                $this->adminMenuTabsModifier($tab['sub_tabs']);
            }
        }
    }

    /**
     * Hook displayAdminOrderTabContent. Since 1.7.7.0
     * {@see https://devdocs.prestashop.com/1.7/modules/core-updates/img/order-view-page-hooks.jpg}
     *
     * @param array $params
     *   Array with the following entries:
     *   - id_order: Order id
     *
     * @return string
     *   The HTML we want to be output on the order details screen.
     *
     * @noinspection PhpUnused  hook
     * @noinspection PhpUnusedParameterInspection
     */
    public function hookDisplayAdminOrderTabLink(array $params): string
    {
        $this->init();
        if ($this->getAcumulusContainer()->getConfig()->getInvoiceStatusSettings()['showInvoiceStatus']) {
            /** @noinspection HtmlUnknownAnchorTarget  false positive */
            return '<li class="nav-item">'
                . '<a class="nav-link" id="orderAcumulusTab" data-toggle="tab" href="#orderAcumulusTabContent"'
                . ' role="tab" aria-controls="orderAcumulusTabContent" aria-expanded="true" aria-selected="false">'
                . '<i class="icon-acumulus"></i>Acumulus</a></li>';
        }
        return '';
    }

    /**
     * Hook displayAdminOrderTabContent. Since 1.7.7.0
     *
     * {@see https://devdocs.prestashop.com/1.7/modules/core-updates/img/order-view-page-hooks.jpg}
     *
     * @param array $params
     *   Array with the following entries:
     *   - id_order: Order id
     *
     * @return string
     *   The HTML we want to be output on the order details screen.
     *
     * @noinspection PhpUnused  hook
     */
    public function hookDisplayAdminOrderTabContent(array $params): string
    {
        $this->init();
        if ($this->getAcumulusContainer()->getConfig()->getInvoiceStatusSettings()['showInvoiceStatus']) {

            // Create form to already load form translations and to set the Source.
            /** @var \Siel\Acumulus\Shop\InvoiceStatusForm $form */
            $form = $this->getAcumulusContainer()->getForm('invoice');
            $orderId = $params['id_order'];
            $source = $this->getAcumulusContainer()->createSource(Source::Order, $orderId);
            $form->setSource($source);

            return $this->renderFormInvoice($form);
        }
        return '';
    }

    /**
     * Renders the form.
     *
     * This method is called by either {@see hookDisplayAdminOrderTabContent()},
     * {@see hookDisplayAdminOrderLeft()} (before 1.7.7), or by the
     * {@see AdminAcumulusInvoiceController::renderForm()} and should return the
     * rendered form.
     *
     * @param \Siel\Acumulus\Shop\InvoiceStatusForm $form
     *
     * @return string
     *   The rendered form.
     */
    public function renderFormInvoice(InvoiceStatusForm $form): string
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
     * Initialises the config: set the configVersion value.
     *
     * @return bool
     *   Success.
     *
     * @throws \JsonException
     */
    protected function initConfig(): bool
    {
        // Set initial config version.
        if (empty($this->getAcumulusContainer()->getConfig()->get(Config::VersionKey))) {
            $values = [Config::VersionKey => Version];
            return $this->getAcumulusContainer()->getConfig()->save($values);
        }
        return true;
    }

    /**
     * Creates the tables this module uses. Called during install() or update
     * (install-4.0.2.php).
     *
     * Actual creation is done by the models. This method might get called via
     * an installation or update script: make it public and call init().
     *
     * @return bool
     *   Success.
     */
    public function createTables(): bool
    {
        return $this->getAcumulusContainer()->getAcumulusEntryManager()->install();
    }

    /**
     * Drops the tables this module uses. Called during uninstall.
     *
     * Actual creation is done by the models.
     *
     * @return bool
     *   Success.
     */
    protected function dropTables(): bool
    {
        return $this->getAcumulusContainer()->getAcumulusEntryManager()->uninstall();
    }

    /**
     * Description.
     *
     * @return string
     *   Description.
     */
    private function getVersion(): string
    {
        return Version::VERSION;
    }
}
