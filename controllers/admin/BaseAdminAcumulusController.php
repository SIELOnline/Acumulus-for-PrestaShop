<?php
/**
 * Validator says: Missing short description in file comment.
 *
 * @author    Buro RaDer, https://burorader.com/
 * @copyright SIEL BV, https://www.siel.nl/acumulus/
 * @license   GPL v3, see license.txt
 */

declare(strict_types=1);

use Siel\Acumulus\Helpers\Form;
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
    protected string $formType = '';
    protected string $icon;

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
    protected function t(string $key): string
    {
        return $this->module->getAcumulusContainer()->getTranslator()->get($key);
    }

    /**
     * Returns the Acumulus Form for the set $this->formType.
     */
    protected function getForm(): Form
    {
        return $this->module->getAcumulusContainer()->getForm($this->formType);
    }

    public function initToolbarTitle(): void
    {
        parent::initToolbarTitle();

        /** @noinspection PhpSwitchStatementWitSingleBranchInspection */
        switch ($this->display) {
            case 'add':
                $this->meta_title = [$this->t("{$this->formType}_form_title")];
                $this->toolbar_title[] = $this->t("{$this->formType}_form_header");
                break;
        }
    }

    /**
     * Processes the form (it was submitted).
     *
     * @throws \Throwable
     */
    public function processSave(): void
    {
        $this->display = 'add';
        $form = $this->getForm();
        try {
            $form->process();
        } catch (Throwable $e) {
            // We handle our "own" exceptions but only when we can process them
            // as we want, i.e. show it as an error at the beginning of the
            // form. That's why we start catching only after we have a form.
            // The messages will be displayed in {@see renderForm()}.
            try {
                $crashReporter = $this->module->getAcumulusContainer()->getCrashReporter();
                $message = $crashReporter->logAndMail($e);
                $form->createAndAddMessage($message, Severity::Exception);
            } catch (Throwable $inner) {
                // We do not know if we have informed the user per mail or
                // screen, so assume not, and rethrow the original exception.
                throw $e;
            }
        }
    }

    /**
     * Renders the form.
     *
     * @return string
     *   The rendered form.
     *
     * @throws \Throwable
     */
    public function renderForm(): string
    {
        $this->show_form_cancel_button = false;
        $this->multiple_fieldsets = true;
        $form = $this->getForm();
        try {
            if (!$this->ajax) {
                Context::getContext()->controller->addCSS(__PS_BASE_URI__ . 'modules/acumulus/views/css/acumulus.css');
                $this->context->controller->addJS(__PS_BASE_URI__ . 'modules/acumulus/views/js/acumulus.js');
            }
            $formMapper = $this->module->getAcumulusContainer()->getFormMapper();
            $this->fields_form = $formMapper->map($form);
            if ($form->isFullPage()) {
                if ($this->formType === 'batch') {
                    // On the batch form we place the send button before the extended
                    // help fieldset.
                    reset($this->fields_form);
                } else {
                    // On other forms we place it in the last fieldset.
                    end($this->fields_form);
                }
                $key = key($this->fields_form);
                $this->fields_form[$key]['form']['submit'] = [
                    'title' => $this->t("button_submit_$this->formType"),
                    'icon' => $this->icon,
                ];
            }
        } catch (Throwable $e) {
            // We handle our "own" exceptions but only when we can process them
            // as we want, i.e. show it as an error at the beginning of the
            // form. That's why we start catching only after we have a form, and
            // stop catching just before display...() our messages in
            // processSave().
            try {
                $crashReporter = $this->module->getAcumulusContainer()->getCrashReporter();
                $message = $crashReporter->logAndMail($e);
                $form->createAndAddMessage($message, Severity::Exception);
            } catch (Throwable $inner) {
                // We do not know if we have informed the user per mail or
                // screen, so assume not, and rethrow the original exception.
                throw $e;
            }
        }
        foreach ($form->getMessages() as $message) {
            if (($message->getSeverity() & Severity::WarningOrWorse) !== 0) {
                $this->displayWarning($message->format(Message::Format_PlainWithSeverity));
            } else {
                $this->displayInformation($message->format(Message::Format_PlainWithSeverity));
            }
        }
        return parent::renderForm() ?? '';
    }

    /**
     * {@inheritdoc}
     *
     * @noinspection ReturnTypeCanBeDeclaredInspection  We have to follow the
     *   parent, which we can't change.
     */
    public function getFieldsValue($obj)
    {
        parent::getFieldsValue($obj);
        $this->fields_value = $this->getForm()->getFormValues();
        return $this->fields_value;
    }
}
