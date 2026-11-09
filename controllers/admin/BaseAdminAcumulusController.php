<?php
/**
 * @noinspection PhpUnused
 * @noinspection LongInheritanceChainInspection  long inheritance chain is in PS
 *
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

        // Initialises the translations.
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
                /** @noinspection UnsupportedStringOffsetOperationsInspection will be an array. */
                $this->toolbar_title[] = $this->t("{$this->formType}_form_header");
                break;
        }
    }

    /**
     * Processes the form (it was submitted).
     *
     * @throws \Throwable
     *
     * @noinspection PhpMissingParentCallCommonInspection Parent method assumes a
     *   controller handles 1 class and that an object of this class is to be inserted or
     *   updated.
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
                $message = $this->module->getAcumulusContainer()->getCrashReporter()->logAndMail($e);
                $form->createAndAddMessage($message, Severity::Exception);
            } catch (Throwable) {
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
        $exceptionMessage = $formHtml = $messageHtml = '';
        try {
            if (!$this->ajax) {
                $this->module->addCssAndJs();
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
                $messageHtml = $this->module->checkForNewRelease();
                $formHtml = parent::renderForm();
            } else {
                if (in_array($this->formType, ['invoice', 'message'])) {
                    $formHtml = $this->module->renderSubForm($form);
                }
            }
            foreach ($form->getMessages() as $message) {
                if (($message->getSeverity() & Severity::WarningOrWorse) !== 0) {
                    $this->displayWarning($message->format(Message::Format_PlainWithSeverity));
                } else {
                    $this->displayInformation($message->format(Message::Format_PlainWithSeverity));
                }
            }
        } catch (Throwable $e) {
            // We process our "own" exceptions, i.e. mail it and show it as an error at
            // the beginning of the form.
            try {
                $message = $this->module->getAcumulusContainer()->getCrashReporter()->logAndMail($e);
                $exceptionMessage = $this->module->renderMessages(
                    $this->module->getAcumulusContainer()->getMessageCollection()->createAndAddMessage($message, Severity::Exception)
                );
            } catch (Throwable $e2) {
                $exceptionMessage = '<div>' . $e->getMessage() . "<br>\n" . $e2->getMessage() . "</div>\n";
            }
        }
        return $exceptionMessage . $messageHtml .$formHtml;
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldsValue($obj): array
    {
        parent::getFieldsValue($obj);
        $this->fields_value = $this->getForm()->getFormValues();
        return $this->fields_value;
    }
}
