<?php
/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 *
 * To see available variables: <?php print_r(get_defined_vars()); ?>
 * To see the current controller object: <?php print_r($this); ?>
 * To call a controller method: <?php $this->exampleMethod(); ?>
 */
?>
<form method="post" id="edit-module-settings" class="form-horizontal">
  <input type="hidden" name="token" value="<?php echo $this->token(); ?>">
  <div class="panel panel-default">
    <div class="panel-heading"><?php echo $this->text('Twig'); ?></div>
    <div class="panel-body">
      <div class="form-group">
        <label class="col-md-2 control-label"><?php echo $this->text('Debug mode'); ?></label>
        <div class="col-md-4">
          <div class="checkbox">
            <label>
              <input type="checkbox" name="settings[twig][debug]" value="1"<?php echo empty($settings['twig']['debug']) ? '' : ' checked'; ?>> <?php echo $this->text('Enabled'); ?>
            </label>
          </div>
          <div class="help-block">
            <div class="text-muted">
            <?php echo $this->text('If enabled you can use native Twig debugger to see available variables in templates {{ dump }}. Otherwise use built-in tool {{ var_dump }} which is probably better'); ?>
            </div>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-2 control-label"><?php echo $this->text('Auto reload'); ?></label>
        <div class="col-md-4">
          <div class="checkbox">
            <label>
              <input type="checkbox" name="settings[twig][auto_reload]" value="1"<?php echo empty($settings['twig']['auto_reload']) ? '' : ' checked'; ?>> <?php echo $this->text('Enabled'); ?>
            </label>
          </div>
          <div class="help-block">
            <div class="text-muted">
            <?php echo $this->text('Recompile Twig template whenever the source code changes. Should be disabled in production!'); ?>
            </div>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-2 control-label"><?php echo $this->text('Strict variables'); ?></label>
        <div class="col-md-4">
          <div class="checkbox">
            <label>
              <input type="checkbox" name="settings[twig][strict_variables]" value="1"<?php echo empty($settings['twig']['strict_variables']) ? '' : ' checked'; ?>> <?php echo $this->text('Enabled'); ?>
            </label>
          </div>
          <div class="help-block">
            <div class="text-muted">
            <?php echo $this->text('If enabled Twig will throw exceptions on invalid variables. Should be disabled in production!'); ?>
            </div>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-2 control-label">
          <?php echo $this->text('Cache'); ?>
        </label>
        <div class="col-md-3">
          <input name="settings[twig][cache]" class="form-control" value="<?php echo $this->escape($settings['twig']['cache']); ?>">
          <div class="help-block">
            <div class="text-muted">
            <?php echo $this->text('A folder within the module directory to keep compiled Twig templates. Recommended name - "cache". If the folder does not exist, it will be created. If not specified, caching will be disabled, which is bad for performance'); ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="panel panel-default">
    <div class="panel-heading"><?php echo $this->text('Options'); ?></div>
    <div class="panel-body">
      <div class="form-group<?php echo $this->error('catalog_limit', ' has-error'); ?>">
        <label class="col-md-2 control-label">
          <?php echo $this->text('Catalog product limit'); ?>
        </label>
        <div class="col-md-3">
          <input name="settings[catalog_limit]" class="form-control" value="<?php echo $this->escape($settings['catalog_limit']); ?>">
          <div class="help-block">
            <?php echo $this->error('catalog_limit'); ?>
            <div class="text-muted">
            <?php echo $this->text('Number of products per page in the product catalog'); ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="panel panel-default">
    <div class="panel-heading"><?php echo $this->text('Images'); ?></div>
    <div class="panel-body">
      <div class="form-group">
        <label class="col-md-2 control-label"><?php echo $this->text('Category page'); ?></label>
        <div class="col-md-4">
          <select name="settings[image_style_category]" class="form-control">
            <?php foreach ($imagestyles as $id => $name) { ?>
            <option value="<?php echo $id; ?>"<?php echo ($settings['image_style_category'] == $id) ? ' selected' : ''; ?>>
            <?php echo $this->escape($name); ?>
            </option>
            <?php } ?>
          </select>
          <div class="help-block">
            <?php echo $this->text('An <a href="@href">image style</a> for first category image on the category page', array('@href' => $this->url('admin/settings/imagestyle'))); ?>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-2 control-label">
            <?php echo $this->text('Category page (child)'); ?>
        </label>
        <div class="col-md-4">
          <select name="settings[image_style_category_child]" class="form-control">
            <?php foreach ($imagestyles as $id => $name) { ?>
            <option value="<?php echo $id; ?>"<?php echo ($settings['image_style_category_child'] == $id) ? ' selected' : ''; ?>>
            <?php echo $this->escape($name); ?>
            </option>
            <?php } ?>
          </select>
          <div class="help-block">
              <?php echo $this->text('An <a href="@href">image style</a> for child categories (if any) on the category page', array('@href' => $this->url('admin/settings/imagestyle'))); ?>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-2 control-label"><?php echo $this->text('Product page'); ?></label>
        <div class="col-md-4">
          <select name="settings[image_style_product]" class="form-control">
            <?php foreach ($imagestyles as $id => $name) { ?>
            <option value="<?php echo $id; ?>"<?php echo ($settings['image_style_product'] == $id) ? ' selected' : ''; ?>>
            <?php echo $this->escape($name); ?>
            </option>
            <?php } ?>
          </select>
          <div class="help-block">
            <?php echo $this->text('An <a href="@href">image style</a> for main (i.e first) image on the product page', array('@href' => $this->url('admin/settings/imagestyle'))); ?>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-2 control-label"><?php echo $this->text('Page'); ?></label>
        <div class="col-md-4">
          <select name="settings[image_style_page]" class="form-control">
            <?php foreach ($imagestyles as $id => $name) { ?>
            <option value="<?php echo $id; ?>"<?php echo ($settings['image_style_page'] == $id) ? ' selected' : ''; ?>>
            <?php echo $this->escape($name); ?>
            </option>
            <?php } ?>
          </select>
          <div class="help-block">
            <?php echo $this->text('An <a href="@href">image style</a> for page images', array('@href' => $this->url('admin/settings/imagestyle'))); ?>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-2 control-label"><?php echo $this->text('Product catalog (grid)'); ?></label>
        <div class="col-md-4">
          <select name="settings[image_style_product_grid]" class="form-control">
            <?php foreach ($imagestyles as $id => $name) { ?>
            <option value="<?php echo $id; ?>"<?php echo ($settings['image_style_product_grid'] == $id) ? ' selected' : ''; ?>>
            <?php echo $this->escape($name); ?>
            </option>
            <?php } ?>
          </select>
          <div class="help-block">
            <?php echo $this->text('An <a href="@href">image style</a> for products in catalog and blocks when "grid" view is selected', array('@href' => $this->url('admin/settings/imagestyle'))); ?>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-2 control-label"><?php echo $this->text('Product catalog (list)'); ?></label>
        <div class="col-md-4">
          <select name="settings[image_style_product_list]" class="form-control">
            <?php foreach ($imagestyles as $id => $name) { ?>
            <option value="<?php echo $id; ?>"<?php echo ($settings['image_style_product_list'] == $id) ? ' selected' : ''; ?>>
            <?php echo $this->escape($name); ?>
            </option>
            <?php } ?>
          </select>
          <div class="help-block">
              <?php echo $this->text('An <a href="@href">image style</a> for products in catalog and blocks when "list" view is selected', array('@href' => $this->url('admin/settings/imagestyle'))); ?>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-2 control-label"><?php echo $this->text('Cart'); ?></label>
        <div class="col-md-4">
          <select name="settings[image_style_cart]" class="form-control">
            <?php foreach ($imagestyles as $id => $name) { ?>
            <option value="<?php echo $id; ?>"<?php echo ($settings['image_style_cart'] == $id) ? ' selected' : ''; ?>>
            <?php echo $this->escape($name); ?>
            </option>
            <?php } ?>
          </select>
          <div class="help-block">
            <?php echo $this->text('An <a href="@href">image style</a> for products in the shopping cart', array('@href' => $this->url('admin/settings/imagestyle'))); ?>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-2 control-label"><?php echo $this->text('Product option'); ?></label>
        <div class="col-md-4">
          <select name="settings[image_style_option]" class="form-control">
            <?php foreach ($imagestyles as $id => $name) { ?>
            <option value="<?php echo $id; ?>"<?php echo ($settings['image_style_option'] == $id) ? ' selected' : ''; ?>>
            <?php echo $this->escape($name); ?>
            </option>
            <?php } ?>
          </select>
          <div class="help-block">
            <?php echo $this->text('An <a href="@href">image style</a> for product options on the product page', array('@href' => $this->url('admin/settings/imagestyle'))); ?>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-2 control-label"><?php echo $this->text('Collection (banners)'); ?></label>
        <div class="col-md-4">
          <select name="settings[image_style_collection_banner]" class="form-control">
            <?php foreach ($imagestyles as $id => $name) { ?>
            <option value="<?php echo $id; ?>"<?php echo ($settings['image_style_collection_banner'] == $id) ? ' selected' : ''; ?>>
            <?php echo $this->escape($name); ?>
            </option>
            <?php } ?>
          </select>
          <div class="help-block">
            <?php echo $this->text('An <a href="@href">image style</a> for banners in the <a href="@href2">file collections</a>', array('@href' => $this->url('admin/settings/imagestyle'), '@href2' => $this->url('admin/content/collection'))); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="panel panel-default">
    <div class="panel-body">
      <div class="row">
        <div class="col-md-2">
          <button class="btn btn-danger reset" name="reset" value="1" onclick="return confirm('Are you sure?');">
            <i class="fa fa-refresh"></i> <?php echo $this->text('Reset'); ?>
          </button>
        </div>
        <div class="col-md-10">
          <div class="btn-toolbar">
            <a href="<?php echo $this->url('admin/module/list'); ?>" class="btn btn-default cancel">
              <i class="fa fa-reply"></i> <?php echo $this->text('Cancel'); ?>
            </a>
            <button class="btn btn-default save" name="save" value="1">
              <i class="fa fa-floppy-o"></i> <?php echo $this->text('Save'); ?>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>