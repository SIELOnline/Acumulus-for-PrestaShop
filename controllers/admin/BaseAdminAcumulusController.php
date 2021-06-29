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

/**
 * BaseAdminAcumulusBatchController provides shared controller functionality.
 *
 * Proudly copied from AdminPreferencesController.
 *
 * Specify more specific type for property $module:
 * @property Acumulus $module
 */
class BaseAdminAcumulusController extends ModuleAdminController
{

    /**
     * The form type.
     *
     * @var string
     */
    protected $formType = '';

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

        parent::__construct();

        // Initializes the translations.
        $this->getForm();
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
                $this->meta_title = array($this->t("{$this->formType}_form_title"));
                $this->toolbar_title[] = $this->t("{$this->formType}_form_header");
                break;
        }
    }

    /**
     * Renders the form.
     *
     * @return string
     *   The rendered form.
     *
     * @throws \Exception
     */
    public function renderForm()
    {
        $this->show_form_cancel_button = true;
        $this->multiple_fieldsets = true;
        $form = $this->getForm();
        if (!$this->ajax) {
            Context::getContext()->controller->addCSS(__PS_BASE_URI__ . 'modules/acumulus/views/css/acumulus.css');
            $this->context->controller->addJS(__PS_BASE_URI__ . 'modules/acumulus/views/js/acumulus.js');
        }
        $formMapper = $this->module->getAcumulusContainer()->getFormMapper();
        $fields_form = $formMapper->map($form);
        if ($form->isFullPage()) {
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
                'title' => $this->t("button_submit_{$this->formType}"),
                'icon' => $this->icon,
            );
        }
        $this->fields_form = $fields_form;

        return parent::renderForm();
    }

    /**
     * Processes the form (it was submitted).
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
