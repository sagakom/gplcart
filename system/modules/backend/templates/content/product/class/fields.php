<form method="post" id="product-class-fields" class="form-horizontal" onsubmit="return confirm();">
  <input type="hidden" name="token" value="<?php echo $token; ?>">
  <div class="panel panel-default">
    <div class="panel-heading clearfix">
      <div class="btn-toolbar pull-right">
        <?php if (!empty($fields)) { ?>
        <button class="btn btn-default" name="save" value="1">
          <i class="fa fa-floppy-o"></i> <?php echo $this->text('Save'); ?>
        </button>
        <?php } ?>
        <a class="btn btn-default" href="<?php echo $this->url("admin/content/product/class/field/{$product_class['product_class_id']}/add"); ?>">
          <i class="fa fa-plus"></i> <?php echo $this->text('Add'); ?>
        </a>
      </div>
    </div>
    <div class="panel-body table-responsive">
      <table class="table fields">
        <thead>
          <tr>
            <th><?php echo $this->text('Name'); ?></th>
            <th><?php echo $this->text('Required'); ?></th>
            <th><?php echo $this->text('Multiple'); ?></th>
            <th><?php echo $this->text('Weight'); ?></th>
            <th><?php echo $this->text('Remove'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($fields)) { ?>
          <tr>
            <td colspan="5">
              <?php echo $this->text('This product class has no fields'); ?>
            <td>
          <tr>
          <?php } else { ?>
          <?php foreach ($fields as $field_id => $field) { ?>
          <tr>
            <td class="middle">
              <?php echo $this->escape($field['title']); ?>
              <input type="hidden" name="fields[<?php echo $field_id; ?>][weight]" value="<?php echo $field['weight']; ?>">
            </td>
            <td class="middle">
              <input type="checkbox" name="fields[<?php echo $field_id; ?>][required]" value="1"<?php echo $field['required'] ? ' checked' : ''; ?>>
            </td>
            <td class="middle">
              <input type="checkbox" name="fields[<?php echo $field_id; ?>][multiple]" value="1"<?php echo $field['multiple'] ? ' checked' : ''; ?>>
            </td>
            <td class="middle">
              <i class="fa fa-arrows handle"></i> <span class="weight"><?php echo $this->escape($field['weight']); ?></span>
            </td>
            <td class="middle">
              <input type="checkbox" name="fields[<?php echo $field_id; ?>][remove]" value="1">
            </td>
          </tr>
          <?php } ?>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</form>