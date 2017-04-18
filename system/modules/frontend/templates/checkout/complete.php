<?php
/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */
?>
<?php if (!empty($complete_message)) { ?>
<div class="alert alert-success">
  <?php echo $complete_message; ?>
</div>
<?php } ?>
<?php if (!empty($complete_templates)) { ?>
<?php foreach ($complete_templates as $complete_template) { ?>
<?php echo $complete_template; ?>
<?php } ?>
<?php } ?>