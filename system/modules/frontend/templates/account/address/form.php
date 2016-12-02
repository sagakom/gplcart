<div>
  <div id="address-form-wrapper">
    <form method="post" id="edit-address" class="form-horizontal">
      <input type="hidden" name="token" value="<?php echo $this->token(); ?>">
      <?php if (!empty($countries)) { ?>
        <div class="form-group country<?php echo $this->error('country', ' has-error'); ?>">
          <label class="col-md-2 control-label">
            <?php echo $this->escape($format_country['name']); ?>
          </label>
          <div class="col-md-6">
            <select class="form-control" name="address[country]">
              <?php if (empty($address['country'])) { ?>
              <option value=""><?php echo $this->text('- select -'); ?></option>
              <?php } ?>
              <?php foreach ($countries as $code => $name) { ?>
              <option value="<?php echo $code; ?>"<?php echo (isset($address['country']) && $address['country'] == $code) ? ' selected' : ''; ?>><?php echo $this->escape($name); ?></option>
              <?php } ?>
            </select>
            <div class="help-block">
                <?php echo $this->error('country'); ?>
            </div>
          </div>
        </div>
      <?php } ?>
      <?php if (!empty($states)) { ?>
        <div class="form-group state_id<?php echo $this->error('state_id', ' has-error'); ?>">
          <label class="col-md-2 control-label">
            <?php if (!empty($format_state_id['required'])) { ?>
            <span class="text-danger">* </span>
            <?php } ?>
            <?php echo $this->escape($format_state_id['name']); ?>
          </label>
          <div class="col-md-6">
            <select class="form-control" name="address[state_id]">
              <?php foreach ($states as $state_id => $state) { ?>
              <option value="<?php echo $state_id; ?>"<?php echo (isset($address['state_id']) && $address['state_id'] == $state_id) ? ' selected' : ''; ?>><?php echo $this->escape($state['name']); ?></option>
              <?php } ?>
            </select>
            <div class="help-block">
               <?php echo $this->error('state_id'); ?>
            </div>
          </div>
        </div>
      <?php } ?>
      <?php if (empty($countries) || !empty($address['country'])) { ?>
        <?php foreach ($format as $key => $value) { ?>
          <div class="form-group <?php echo $key; ?><?php echo $this->error($key, ' has-error'); ?>">
            <label class="col-md-2 control-label">
              <?php if (!empty($value['required'])) { ?>
              <span class="text-danger">* </span>
              <?php } ?>
              <?php echo $this->escape($value['name']); ?>
            </label>
            <div class="col-md-6">
              <input name="address[<?php echo $key; ?>]" maxlength="255" class="form-control" value="<?php echo isset($address[$key]) ? $this->escape($address[$key]) : ''; ?>">
              <div class="help-block"><?php echo $this->error($key); ?></div>
            </div>
          </div>
        <?php } ?>
        <div class="row">
          <div class="col-md-2 text-right">
            <a class="btn btn-default" href="<?php echo $this->url("account/{$this->uid}/address"); ?>">
              <?php echo $this->text('Cancel'); ?>
            </a>
          </div>
          <div class="col-md-10">
            <button class="btn btn-default save" name="save" value="1">
              <i class="fa fa-floppy-o"></i> <?php echo $this->text('Save'); ?>
            </button>
          </div>
        </div>
      <?php } ?>
    </form>
  </div>
</div>