<?php if (!defined('CIVI_SETUP')): exit("Installation plugins must only be loaded by the installer.\n");
endif; ?>
<h2><?php echo ts('Sample Data'); ?></h2>

<p>
  <label for="loadGenerated"><span><?php echo ts('Fill system with fake information'); ?></span> <input id="loadGenerated" type="checkbox" name="civisetup[loadGenerated]" value=1 <?php echo $model->loadGenerated ? "checked='checked'" : ""; ?> /></label> <br />
  <span class="advancedTip"><?php echo ts("To initialize a demo site, load sample data about fake people. Generated in English (United States)."); ?></span><br />
</p>
