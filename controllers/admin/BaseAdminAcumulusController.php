<?php
/**
 * Validator says: Missing short description in file comment.
 *
 * @author    Buro RaDer, https://burorader.com/
 * @copyright SIEL BV, https://www.siel.nl/acumulus/
 * @license   GPL v3, see license.txt
 */

use Siel\Acumulus\Helpers\Severity;
use Siel\Acumulus\Helpers\Message;
use Siel\Acumulus\Shop\BatchFormTranslations;
use Siel\Acumulus\Shop\ConfigFormTranslations;

/**
 * BaseAdminAcumulusBatchController provides shared controller functionality.
 *
 * Proudly copied from AdminPreferencesController.
 */
class BaseAdminAcumulusController extends AdminController
{
    /** @var Acumulus */
    protected $module = null;

    /**
     * The form type.
     *
     * @var string
     */
    protected $formType = '';

    /**
     * The form title.
     *
     * @var string
     */
    protected $title;

    /**
     * The form icon.
     *
     * @var string
     */
    protected $icon;

    public function __construct()
    {
        $this->className = '';
        $this->table = '';
        $this->display = 'add';
        $this->bootstrap = true;

        // Initialization.
        require_once(dirname(__FILE__) . '/../../acumulus.php');
        $this->module = new Acumulus();
        // Init order problem: getAcumulusConfig() initializes the autoloader,
        // so we need to create that before creating the translations.
        $acumulusContainer = $this->module->getAcumulusContainer();
        $translations = $this->formType === 'batch' ? new BatchFormTranslations() : new ConfigFormTranslations();
        $acumulusContainer->getTranslator()->add($translations);

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
        return $this->module->getAcumulusContainer()->getTranslator()->get($key);
    }


    /**
     * @return \Siel\Acumulus\Helpers\Form
     */
    protected function getForm()
    {
        return $this->module->getAcumulusContainer()->getForm($this->formType);
    }

    public function initToolbarTitle()
    {
        parent::initToolbarTitle();

        switch ($this->display) {
            case 'add':
                $this->meta_title = array($this->t("{$this->formType}_form_header"));
                $this->toolbar_title[] = $this->t("{$this->formType}_form_title");
                break;
        }
    }

    /**
     * Renders the form.
     *
     * @return string
     *   The rendered form.
     * @throws \SmartyException
     */
    public function renderForm()
    {
        $this->show_form_cancel_button = true;
        $this->multiple_fieldsets = true;
        $form = $this->getForm();
        $formMapper = $this->module->getAcumulusContainer()->getFormMapper();
        $fields_form = $formMapper->map($form);
        if ($this->formType === 'batch') {
            // On the batch form we place the send button before the extended
            // help fieldset.
            reset($fields_form);
        } else {
            // On other forms we place it in the last fieldset.
            end($fields_form);
        }
        $key = key($fields_form);
        $fields_form[$key]['form']['submit'] = array(
            'title' => $this->title,
            'icon' => $this->icon,
        );
        $this->fields_form = $fields_form;

        return parent::renderForm();
    }

    /**
     * Processes the form (if it was submitted).
     */
    public function processSave()
    {
        $form = $this->getForm();
        $form->process();
        // Force the creation of the fields to get connection error messages
        // shown.
        $form->getFields();
        foreach ($form->getMessages() as $message) {
            if (($message->getSeverity() & Severity::WarningOrWorse) !== 0) {
                $this->displayWarning($message->format(Message::Format_PlainWithSeverity));
            } else {
                $this->displayInformation($message->format(Message::Format_PlainWithSeverity));
            }
        }
        $this->display = 'add';
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsValue($obj)
    {
        parent::getFieldsValue($obj);
        $this->fields_value = $this->getForm()->getFormValues();
        return $this->fields_value;
    }
}
