<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace core\controllers\admin;

use core\Controller;
use core\classes\Tool;

class Settings extends Controller
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Displays edit settings form
     */
    public function editSettings()
    {
        $this->controlAccessSuperAdmin();

        $default = $this->getDefaultSettings();

        foreach ($default as $key => $value) {
            $this->setData("settings.$key", $this->config($key, $value));
        }

        $this->submitSettings();
        $this->setDataEditSettings();

        $this->setTitleEditSettings();
        $this->setBreadcrumbEditSettings();
        $this->outputEditSettings();
    }

    /**
     * Sets titles on the settings form page
     */
    protected function setTitleEditSettings()
    {
        $this->setTitle($this->text('Settings'));
    }

    /**
     * Sets breadcrumbs on the settings form page
     */
    protected function setBreadcrumbEditSettings()
    {
        $breadcrumbs[] = array(
            'url' => $this->url('admin'),
            'text' => $this->text('Dashboard'));

        $this->setBreadcrumbs($breadcrumbs);
    }

    /**
     * Renders settings page
     */
    protected function outputEditSettings()
    {
        $this->output('settings/settings');
    }

    /**
     * Returns an array of settings with their default values
     * @return array
     */
    protected function getDefaultSettings()
    {
        return array(
            'cron_key' => '',
            'error_level' => 2,
            'gapi_email' => '',
            'gapi_certificate' => '',
            'email_method' => 'mail',
            'smtp_auth' => 1,
            'smtp_secure' => 'tls',
            'smtp_host' => array('smtp.gmail.com'),
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_port' => 587
        );
    }

    /**
     * Prepares settings values before passing them to template
     */
    protected function setDataEditSettings()
    {
        $smtp_host = $this->getData('settings.smtp_host');
        $this->setData('settings.smtp_host', implode("\n", (array) $smtp_host));
    }

    /**
     * Saves submitted settings
     */
    protected function submitSettings()
    {
        if (!$this->isPosted('save')) {
            return;
        }

        $this->setSubmitted('settings');
        $this->validateSettings();

        if ($this->hasErrors('settings')) {
            return;
        }

        $this->updateSettings();
    }

    /**
     * Updates common setting with submitted values
     */
    protected function updateSettings()
    {
        $this->controlAccess('settings_edit');

        if ($this->isPosted('delete_gapi_certificate')) {
            unlink(GC_FILE_DIR . '/' . $this->config('gapi_certificate'));
            $this->config->reset('gapi_certificate');
        }

        $allowed = $this->config->get();
        $submitted = $this->getSubmitted();
        $save = array_intersect_key($submitted, $allowed);

        foreach ($save as $key => $value) {
            $this->config->set($key, $value);
        }

        $message = $this->text('Settings have been updated');
        $this->redirect('', $message, 'success');
    }

    /**
     * Validates submitted settings
     */
    protected function validateSettings()
    {
        $this->setSubmittedArray('smtp_host');

        $cron_key = $this->getSubmitted('cron_key');
        if (empty($cron_key)) {
            $this->setSubmitted('cron_key', Tool::randomString());
        }

        $this->addValidator('gapi_email', array(
            'email' => array()
        ));

        $file = $this->request->file('gapi_certificate');

        $this->addValidator('gapi_certificate', array(
            'upload' => array('file' => $file)
        ));

        $this->setValidators();

        $uploaded = $this->getValidatorResult('gapi_certificate');
        $this->setSubmitted('gapi_certificate', $uploaded);
    }

}
