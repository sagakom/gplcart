<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\controllers\backend;

use gplcart\core\models\Mail as MailModel;
use gplcart\core\controllers\backend\Controller as BackendController;

class Settings extends BackendController
{

    /**
     * Mail model class instance
     * @var \gplcart\core\models\Mail $mail
     */
    protected $mail;

    /**
     * Constructor
     */
    public function __construct(MailModel $mail)
    {
        parent::__construct();

        $this->mail = $mail;
    }

    /**
     * Displays edit settings form
     */
    public function editSettings()
    {
        $this->setTitleEditSettings();
        $this->setBreadcrumbEditSettings();

        $this->controlAccessEditSettings();

        $this->setData('settings', $this->getSettings());
        $this->setData('timezones', gplcart_timezones());
        $this->setData('mailers', $this->mail->getMailers());

        $this->submitSettings();

        $this->outputEditSettings();
    }

    /**
     * Controls access to edit settings
     */
    protected function controlAccessEditSettings()
    {
        if (!$this->isSuperadmin()) {
            $this->outputHttpStatus(403);
        }
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
            'error_live_report' => 0,
            'mailer' => '',
            'gapi_browser_key' => '',
            'timezone' => 'Europe/London'
        );
    }

    /**
     * Returns an array of settings
     * @return array
     */
    protected function getSettings()
    {
        $default = $this->getDefaultSettings();
        $saved = $this->config();

        return gplcart_array_merge($default, $saved);
    }

    /**
     * Saves submitted settings
     */
    protected function submitSettings()
    {
        if ($this->isPosted('delete_cached_assets')) {
            $this->clearCacheAssetsSettings();
        } else if ($this->isPosted('save') && $this->validateSettings()) {
            $this->updateSettings();
        }
    }

    /**
     * Validates submitted settings
     * @return bool
     */
    protected function validateSettings()
    {
        $this->setSubmitted('settings');

        if (!$this->getSubmitted('cron_key')) {
            $this->setSubmitted('cron_key', gplcart_string_random());
        }

        return !$this->hasErrors();
    }

    /**
     * Deletes all aggregated assets
     */
    protected function clearCacheAssetsSettings()
    {
        $deleted = gplcart_file_delete_recursive(GC_COMPRESSED_ASSET_DIR);

        if ($deleted) {
            $this->redirect('', $this->text('Cache has been cleared'), 'success');
        }

        $this->redirect('');
    }

    /**
     * Updates common setting with submitted values
     */
    protected function updateSettings()
    {
        $this->controlAccess('settings_edit');

        $submitted = $this->getSubmitted();

        foreach ($submitted as $key => $value) {
            $this->config->set($key, $value);
        }

        $message = $this->text('Settings have been updated');
        $this->redirect('', $message, 'success');
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
        $breadcrumb = array(
            'url' => $this->url('admin'),
            'text' => $this->text('Dashboard')
        );

        $this->setBreadcrumb($breadcrumb);
    }

    /**
     * Renders settings page
     */
    protected function outputEditSettings()
    {
        $this->output('settings/common');
    }

}
