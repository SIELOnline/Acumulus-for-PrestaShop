<?php
/**
 * @author: Buro RaDer http://www.burorader.com/
 * @copyright: SIEL BV https://www.siel.nl/acumulus/
 * @license: GPLv3, see license.txt
 */

 /**
 * The AcumulusCustomiseInvoice module class contains plumbing and example code
 * to react to hooks triggered by the Acumulus module. These hooks allow you to:
 * - Prevent sending an invoice to Acumulus.
 * - Customise the invoice before it is sent to Acumulus.
 * - Process the results of sending the invoice to Acumulus.
 *
 * Usage of this module:
 * You can use and modify this example module as you like:
 * - only register the hooks you are going to use.
 * - add your own hook handling in those handler methods.
 *
 * Or, if you already have a module with custom code, you can add this code
 * over there:
 * - any hook handling code only copy the hooks you are going to use.
 * - any registerHook() call: only copy those hooks that you need.
 *
 * Documentation for the hooks:
 * The hooks defined by the Acumulus module:
 * 1) actionAcumulusInvoiceCreated
 * 2) actionAcumulusInvoiceSendBefore
 * 3) actionAcumulusInvoiceSendAfter
 *
 * ad 1)
 * This hook is triggered after the raw invoice has been created but before
 * it is "completed". The raw invoice contains all data from the original order
 * or refund needed to create an invoice in the Acumulus format. The raw
 * invoice needs to be completed before it can be sent. Completing includes:
 * - Determining vat rates for those lines that do not yet have one (mostly
 *   discount lines or other special lines like processing or payment costs).
 * - Correcting vat rates if they were based on dividing a vat amount (in
 *   cents) by a price (in cents).
 * - Splitting discount lines over multiple vat rates.
 * - Making prices ex vat more precise to prevent invoice amount differences.
 * - Converting non Euro currencies (future feature).
 * - Flattening composed products or products with options.
 *
 * So with this hook you can make changes to the raw invoice based on your
 * specific situation. By returning null, you can prevent having the invoice
 * been sent to Acumulus. Normally you should prefer the 2nd hook, where you
 * can assume that the invoice has been flattened and all fields are filled in
 * and have valid values.
 *
 * However, in some specific cases this hook may be needed, e.g. setting or
 * correcting tax rates before the completor strategies are executed.
 *
 * ad 2)
 * This hook is triggered just before the invoice will be sent to Acumulus.
 * You can make changes to the invoice or add warnings or errors to the Result
 * object.
 *
 * Typical use cases are:
 * - Template, account number, or cost center selection based on order
 *   specifics, e.g. in a multi-shop environment.
 * - Adding descriptive info to the invoice or invoice lines based on custom
 *   order meta data or data from not supported modules.
 * - Correcting payment info based on specific knowledge of your situation or
 *   on payment modules not supported by this module.
 *
 * ad 3)
 * This hook is triggered after the invoice has been sent to Acumulus. The
 * Result object will tell you if there was an exception or if errors or
 * warnings were returned by the Acumulus API. On success, the entry id and
 * token for the newly created invoice in Acumulus are available, so you can
 * e.g. retrieve the pdf of the Acumulus invoice.
 *
 * External Resources:
 * - https://apidoc.sielsystems.nl/content/invoice-add.
 * - https://apidoc.sielsystems.nl/content/warning-error-and-status-response-section-most-api-calls
 *
 */
class Acumulus_Customise_Invoice extends Module
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
    public static $module_version = '5.0.0';

    /** @var array */
    protected $options = array();

    /**
     * Do not call directly, use the getter getAcumulusContainer().
     *
     * @var \Siel\Acumulus\Helpers\ContainerInterface
     */
    private $container = null;

    public function __construct()
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $this->name = 'acumulus_customise_invoice';
        /** @noinspection PhpUndefinedFieldInspection */
        $this->tab = 'billing_invoicing';
        /** @noinspection PhpUndefinedFieldInspection */
        $this->version = self::$module_version;
        /** @noinspection PhpUndefinedFieldInspection */
        $this->author = 'Acumulus';
        /** @noinspection PhpUndefinedFieldInspection */
        $this->need_instance = 0;
        /** @noinspection PhpUndefinedFieldInspection */
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => '1.9');
        /** @noinspection PhpUndefinedFieldInspection */
        $this->dependencies = array('acumulus');
        /** @noinspection PhpUndefinedFieldInspection */
        $this->bootstrap = true;
        /** @noinspection PhpUndefinedFieldInspection */
        $this->is_configurable = false;

        parent::__construct();

        $this->displayName = $this->l('Customise Acumulus Invoice');
        $this->description = $this->l('Example module that shows how to react to the hooks as defined by the Acumulus module.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

    }

    /**
     * Loads the Acumulus library and creates a configuration object so this
     * custom plugin has access to the Acumulus classes, configuration and
     * constants.
     *
     * Do not call directly, use getAcumulusContainer().
     */
    private function init()
    {
        if ($this->container === null) {
            /** @noinspection PhpUndefinedClassInspection */
            $languageCode = isset(Context::getContext()->language) ? Context::getContext()->language->iso_code : 'nl';
            $this->container = new \Siel\Acumulus\Helpers\Container('PrestaShop', $languageCode);
            $this->container->getTranslator()->add(new \Siel\Acumulus\Shop\ModuleTranslations());
        }
    }

    /**
     * @return \Siel\Acumulus\Helpers\ContainerInterface
     */
    private function getAcumulusContainer()
    {
        $this->init();
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function enable($force_all = false)
    {
        return parent::enable($force_all)
            && $this->registerHook('actionAcumulusInvoiceCreated')
            && $this->registerHook('actionAcumulusInvoiceSendBefore')
            && $this->registerHook('actionAcumulusInvoiceSendAfter');

    }

    /**
     * {@inheritdoc}
     */
    public function disable($force_all = false)
    {
	    return $this->unregisterHook('actionAcumulusInvoiceCreated')
            && $this->unregisterHook('actionAcumulusInvoiceSendBefore')
            && $this->unregisterHook('actionAcumulusInvoiceSendAfter')
            && parent::disable($force_all);
    }

    /**
     * Processes the hook triggered after the raw invoice has been created.
     *
     * @param array $params
     *   Array with the following entries:
     *   - 'invoice' =>  array|null
     *   The invoice in Acumulus format as will be sent to Acumulus or null if
     *   another filter already decided that the invoice should not be sent to
     *   Acumulus.
     *   - 'source' => \Siel\Acumulus\Invoice\Source
     *   Wrapper around the original PrestaShop order or refund for which the
     *   invoice has been created.
     *   - 'localResult' => \Siel\Acumulus\Invoice\Result
     *   Any local error or warning messages that were created locally.
     */
    public function hookactionAcumulusInvoiceCreated(array $params)
    {
        /** @var array $invoice */
        $invoice = &$params['invoice'];
        /** @var \Siel\Acumulus\Invoice\Source $invoiceSource */
        $invoiceSource = $params['source'];
        /** @var \Siel\Acumulus\Invoice\Result $localResult */
        $localResult = $params['localResult'];

        $this->init();
        // Here you can make changes to the raw invoice based on your specific
        // situation, e.g. setting or correcting tax rates before the completor
        // strategies execute.

        // NOTE: the example below is now an option in the advanced settings:
        // Prevent sending 0-amount invoices (free products).
        if (empty($invoice) || $invoice['customer']['invoice'][\Siel\Acumulus\Meta::InvoiceAmountInc] == 0) {
            $invoice = null;
        }
    }

    /**
     * Processes the hook triggered before an invoice will be sent to Acumulus.
     *
     * @param array $params
     *   Array with the following entries:
     *   - 'invoice' =>  array|null
     *   The invoice in Acumulus format as will be sent to Acumulus or null if
     *   another filter already decided that the invoice should not be sent to
     *   Acumulus.
     *   - 'source' => \Siel\Acumulus\Invoice\Source
     *   Wrapper around the original PrestaShop order or refund for which the
     *   invoice has been created.
     *   - 'localResult' => \Siel\Acumulus\Invoice\Result
     *   Any local error or warning messages that were created locally.
     */
    public function hookactionAcumulusInvoiceSendBefore(array $params)
    {
        /** @var array $invoice */
        $invoice = &$params['invoice'];
        /** @var \Siel\Acumulus\Invoice\Source $invoiceSource */
        $invoiceSource = $params['source'];
        /** @var \Siel\Acumulus\Invoice\Result $localResult */
        $localResult = $params['localResult'];

        $this->init();
        // Here you can make changes to the invoice based on your specific
        // situation, e.g. setting the payment status to its correct value:
        $invoice['customer']['invoice']['testpaymentstatus'] = $this->isOrderPaid($invoiceSource) ? \Siel\Acumulus\Api::PaymentStatus_Paid : \Siel\Acumulus\Api::PaymentStatus_Due;
    }

    /**
     * Processes the hook triggered after an invoice has been sent to Acumulus.
     *
     * @param array $params
     *   Array with the following entries:
     *   - 'invoice' =>  array|null
     *   The invoice in Acumulus format as will be sent to Acumulus or null if
     *   another filter already decided that the invoice should not be sent to
     *   Acumulus.
     *   - 'source' => \Siel\Acumulus\Invoice\Source
     *   Wrapper around the original PrestaShop order or refund for which the
     *   invoice has been created.
     *   - 'result' => \Siel\Acumulus\Invoice\Result
     *   The result as sent back by Acumulus + any local messages and warnings.
     */
    public function hookactionAcumulusInvoiceSendAfter(array $params)
    {
        /** @var array $invoice */
        $invoice = $params['invoice'];
        /** @var \Siel\Acumulus\Invoice\Source $invoiceSource */
        $invoiceSource = $params['source'];
        /** @var \Siel\Acumulus\Invoice\Result $localResult */
        $result = $params['result'];

        $this->init();
        if ($result->getException()) {
            // Serious error:
            if ($result->isSent()) {
                // During sending.
            }
            else {
                // Before sending.
            }
        }
        elseif ($result->hasError()) {
            // Invoice was sent to Acumulus but not created due to errors in the
            // invoice.
        }
        else {
            // Sent successfully, invoice has been created in Acumulus:
            if ($result->getWarnings()) {
                // With warnings.
            }
            else {
                // Without warnings.
            }

            $acumulusInvoice = $result->getResponse();
            // Check if an entry id was created.
            $acumulusInvoice = $result->getResponse();
            if (!empty($acumulusInvoice['entryid'])) {
                $token = $acumulusInvoice['token'];
                $entryId = $acumulusInvoice['entryid'];
            }
            else {
                // If the invoice was sent as a concept, no entryid will be returned.
            }
        }
    }


    /**
     * Returns if the order has been paid or not.
     *
     * OpenCart does not store any payment data, so determining the payment
     * status is not really possible for the Acumulus extension. Therefore this
     * is a very valid example of a change you may want to make to the invoice
     * before it is being send.
     *
     * Please fill in your own logic here in this method.
     *
     * @param \Siel\Acumulus\Invoice\Source $invoiceSource
     *   Wrapper around the original order for which the invoice has been
     *   created.
     *
     * @return bool
     *   True if the order has been paid, false otherwise.
     *
     */
    private function isOrderPaid(\Siel\Acumulus\Invoice\Source $invoiceSource)
    {
        /** @var \Order|\OrderSlip */
        $order = $invoiceSource->getSource();
        //$this->getAcumulusContainer()->getLog()->info('AcumulusCustomiseInvoice::isOrderPaid(): invoiceSource = ' . var_export($invoiceSource->getSource(), true));
        return true;
    }
}
