<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\controllers\frontend;

use gplcart\core\models\Oauth as OauthModel;
use gplcart\core\controllers\frontend\Controller as FrontendController;

/**
 * Handles incoming requests and outputs data related to Oauth functionality
 */
class Oauth extends FrontendController
{

    /**
     * Oauth model instance
     * @var \gplcart\core\models\Oauth $oauth
     */
    protected $oauth;

    /**
     * The current Oauth provider
     * @var array
     */
    protected $data_provider;

    /**
     * The current received code from provider
     * @var string
     */
    protected $data_code;

    /**
     * The current received state hash from provider
     * @var type 
     */
    protected $data_state;

    /**
     * The current token data
     * @var array
     */
    protected $data_token;

    /**
     * Processed authorization result
     * @var mixed 
     */
    protected $data_result;

    /**
     * URL to redirect to after authorization
     * @var string
     */
    protected $data_url;

    /**
     * @param OauthModuleModel $oauth
     */
    public function __construct(OauthModel $oauth)
    {
        parent::__construct();

        $this->oauth = $oauth;
    }

    /**
     * Callback for Oauth returning URL
     * @param string $provider_id
     */
    public function callbackOauth()
    {
        $this->setReceivedDataOauth();
        $this->setTokenOauth();
        $this->setResultOauth();
        $this->redirectOauth();
    }

    /**
     * Set and validates received data from Oauth provider
     */
    protected function setReceivedDataOauth()
    {
        $this->data_code = $this->request->get('code');
        $this->data_state = $this->request->get('state');

        if (empty($this->data_code) || empty($this->data_state)) {
            $this->outputHttpStatus(403);
        }

        $parsed = $this->oauth->parseState($this->data_state);

        if (empty($parsed['id'])) {
            $this->outputHttpStatus(403);
        }

        if (!$this->oauth->isValidState($this->data_state, $parsed['id'])) {
            $this->outputHttpStatus(403);
        }

        $this->data_provider = $this->oauth->getProvider($parsed['id']);

        if (empty($this->data_provider)) {
            $this->outputHttpStatus(403);
        }

        $this->data_url = $parsed['url'];
    }

    /**
     * Does final redirect after authorization
     */
    protected function redirectOauth()
    {
        if (isset($this->data_result['message'])) {
            $this->setMessage($this->data_result['message'], $this->data_result['severity'], true);
        }

        if (isset($this->data_result['redirect'])) {
            $this->redirect($this->data_result['redirect']);
        }

        $this->redirect($this->data_url);
    }

    /**
     * Set received token data
     * @return array
     */
    protected function setTokenOauth()
    {
        $this->data_token = $this->oauth->getToken($this->data_provider, array('code' => $this->data_code));

        if (empty($this->data_token['access_token'])) {
            $this->outputHttpStatus(403);
        }

        return $this->data_token;
    }

    /**
     * Set authorization result
     * @return array
     */
    protected function setResultOauth()
    {
        $this->data_result = $this->oauth->process($this->data_provider, $this->data_token['access_token']);

        if (empty($this->data_result)) {
            $this->data_result['severity'] = 'warning';
            $this->data_result['message'] = $this->text('An error occurred');
        }

        return $this->data_result;
    }

}
