<?php
/**
 * Validator says: Missing short description in file comment.
 *
 * @author    Buro RaDer / SIEL Acumulus
 * @copyright 2016 Buro RaDer
 * @license   see license.txt
 */

use Siel\Acumulus\PrestaShop\Helpers\FormMapper;
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
        require_once(__DIR__ . '/../../acumulus.php');
        $this->module = new Acumulus();
        // Init order problem: getAcumulusConfig() initializes the autoloader,
        // so we need to create that before creating the translations.
        $acumulusConfig = $this->module->getAcumulusConfig();
        $translations = $this->formType === 'batch' ? new BatchFormTranslations() : new ConfigFormTranslations();
        $acumulusConfig->getTranslator()->add($translations);

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
        return $this->module->getAcumulusConfig()->getTranslator()->get($key);
    }


    /**
     * @return \Siel\Acumulus\Shop\BatchForm
     */
    protected function getForm()
    {
        return $this->module->getAcumulusConfig()->getForm($this->formType);
    }

    public function initToolbarTitle()
    {
        parent::initToolbarTitle();

        switch ($this->display) {
            case 'add':
                $this->toolbar_title[] = $this->t("{$this->formType}_form_title");
                break;
        }
    }

    /**
     * Renders the form.
     *
     * @return string
     *   The rendered form.
     */
    public function renderForm()
    {
        $this->show_form_cancel_button = true;
        $this->multiple_fieldsets = true;
        $form = $this->getForm();
        $formMapper = new FormMapper();
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
        foreach ($form->getErrorMessages() as $message) {
            $this->displayWarning($message);
        }
        foreach ($form->getSuccessMessages() as $message) {
            $this->displayInformation($message);
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