<?php
/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */
?>
<div class="row">
  <div class="col-md-6">
    <?php echo $pane_summary; ?>
    <?php echo $pane_customer; ?>
    <?php echo $pane_log; ?>
  </div>
  <div class="col-md-6">
    <?php echo $pane_components; ?>
    <?php echo $pane_shipping_address; ?>
    <?php echo $pane_comment; ?>
    <div class="panel panel-default hidden-print">
      <div class="panel-body">
        <div class="btn-toolbar">
          <button class="btn btn-default" type="button" onclick="window.print();">
            <?php echo $this->text('Print'); ?>
          </button>
          <?php if($this->access('order_edit') && empty($order['log'])) { ?>
          <a class="btn btn-default" href="<?php echo $this->url("checkout/edit/{$order['order_id']}"); ?>">
            <?php echo $this->text('Edit'); ?>
          </a>
        <?php } ?>
        </div>
      </div>
    </div>
  </div>
</div>