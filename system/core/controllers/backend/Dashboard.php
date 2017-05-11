<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\controllers\backend;

use gplcart\core\models\Order as OrderModel,
    gplcart\core\models\Price as PriceModel,
    gplcart\core\models\Report as ReportModel,
    gplcart\core\models\Review as ReviewModel,
    gplcart\core\models\Product as ProductModel;
use gplcart\core\controllers\backend\Controller as BackendController;

/**
 * Handles incoming requests and outputs data related to admin dashboard
 */
class Dashboard extends BackendController
{

    /**
     * Product model instance
     * @var \gplcart\core\models\Product $product
     */
    protected $product;

    /**
     * Price model instance
     * @var \gplcart\core\models\Price $price
     */
    protected $price;

    /**
     * Order model instance
     * @var \gplcart\core\models\Order $order
     */
    protected $order;

    /**
     * Report model instance
     * @var \gplcart\core\models\Report $report
     */
    protected $report;

    /**
     * Max items to be shown in the dashboard panels
     * @var integer
     */
    protected $dashboard_limit;

    /**
     * Review model instance
     * @var \gplcart\core\models\Review $review
     */
    protected $review;

    /**
     * Constructor
     * @param ProductModel $product
     * @param PriceModel $price
     * @param OrderModel $order
     * @param ReportModel $report
     * @param ReviewModel $review
     */
    public function __construct(ProductModel $product, PriceModel $price,
            OrderModel $order, ReportModel $report, ReviewModel $review
    )
    {
        parent::__construct();

        $this->price = $price;
        $this->order = $order;
        $this->report = $report;
        $this->review = $review;
        $this->product = $product;
    }

    /**
     * Displays the admin dashboard page
     */
    public function dashboard()
    {
        $this->toggleIntroDashboard();
        $this->setTitleDashboard();
        $this->setDataContentDashboard();
        $this->outputDashboard();
    }

    /**
     * Sets dashboard content data
     */
    protected function setDataContentDashboard()
    {
        $panels = $this->getPanelsDashboard();
        $columns = $this->config('dashboard_columns', 2);
        $splitted = gplcart_array_split($panels, $columns);

        $this->setData('columns', $columns);
        $this->setData('dashboard', $splitted);

        if ($this->config('intro', false) && $this->isSuperadmin()) {
            $this->setData('intro', $this->render('dashboard/intro', array(
                        'items' => $this->getIntroItemsDashboard())));
        }
    }

    /**
     * Returns an array of dashboard intro items
     */
    protected function getIntroItemsDashboard()
    {
        $items = array();
        foreach (array('header', 'settings', 'product', 'module') as $i => $item) {
            $items[$item] = array('weight' => $i, 'rendered' => $this->render("dashboard/intro/$item"));
        }

        $this->hook->fire('dashboard.intro', $items, $this);
        gplcart_array_sort($items);
        return $items;
    }

    /**
     * Returns an array of sorted panels
     * @return array
     */
    protected function getPanelsDashboard()
    {
        $panels = $this->getDefaultPanelsDashboard();
        $this->hook->fire('dashboard.panels', $panels, $this);
        gplcart_array_sort($panels);
        return $panels;
    }

    /**
     * Returns an array of default dashboard panels
     * @return array
     */
    protected function getDefaultPanelsDashboard()
    {
        $panels = array();
        $panels['user'] = array('rendered' => $this->renderPanelUsersDashboard());
        $panels['order'] = array('rendered' => $this->renderPanelOrdersDashboard());
        $panels['event'] = array('rendered' => $this->renderPanelEventsDashboard());
        $panels['summary'] = array('rendered' => $this->renderPanelSummaryDashboard());
        return $panels;
    }

    /**
     * Toggles dashboard page from post-installation intro to normal view
     */
    protected function toggleIntroDashboard()
    {
        if ($this->isQuery('skip_intro')) {
            $this->config->reset('intro');
            $this->redirect();
        }
    }

    /**
     * Returns rendered recent users panel
     */
    protected function renderPanelUsersDashboard()
    {
        $options = array(
            'limit' => array(0, $this->config('dashboard_limit', 10))
        );

        $users = $this->user->getList($options);
        return $this->render('dashboard/panels/users', array('users' => $users));
    }

    /**
     * Returns rendered recent orders panel
     */
    protected function renderPanelOrdersDashboard()
    {
        $options = array('limit' => array(0, $this->config('dashboard_limit', 10)));
        $orders = $this->order->getList($options);

        array_walk($orders, function (&$order) {
            $order['is_new'] = $this->order->isNew($order);
            $order['total_formatted'] = $this->price->format($order['total'], $order['currency']);
        });

        return $this->render('dashboard/panels/orders', array('orders' => $orders));
    }

    /**
     * Returns rendered recent events panel
     */
    protected function renderPanelEventsDashboard()
    {
        $events = array();
        $severities = $this->report->getSeverities();

        foreach (array_keys($severities) as $severity) {

            $options = array(
                'severity' => $severity,
                'limit' => array(0, $this->config('dashboard_limit', 10))
            );

            $items = (array) $this->report->getList($options);

            foreach ($items as &$item) {
                $variables = empty($item['data']['variables']) ? array() : (array) $item['data']['variables'];
                $message = empty($item['translatable']) ? $item['text'] : $this->text($item['text'], $variables);
                $item['message'] = strip_tags($message);
            }

            if (empty($items)) {
                continue;
            }

            $events[$severity] = $items;
        }
        return $this->render('dashboard/panels/events', array('events' => $events));
    }

    /**
     * Returns rendered summary panel
     */
    protected function renderPanelSummaryDashboard()
    {
        $options = array('count' => true);

        $data = array(
            'user_total' => $this->user->getList($options),
            'order_total' => $this->order->getList($options),
            'review_total' => $this->review->getList($options),
            'product_total' => $this->product->getList($options)
        );

        return $this->render('dashboard/panels/summary', $data);
    }

    /**
     * Sets titles on the admin dashboard page
     */
    protected function setTitleDashboard()
    {
        $this->setTitle($this->text('Dashboard'), false);
    }

    /**
     * Renders the admin dashboard page
     */
    protected function outputDashboard()
    {
        $this->output('dashboard/dashboard');
    }

}
