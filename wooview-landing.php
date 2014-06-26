<div class="wrap">
  
  <div class="wv-content" style="background-color: #fff;">
    
    <div class="grid grid-pad banner">
      <div class="col-1-1">
         <div class="content">
             
         </div>
      </div>
    </div>
    
    <div class="grid grid-pad">
      <div class="col-1-1">
         <div class="content">
             <h1>WooView</h1>
             <p>Manage your WooCommerce shop anywhere, anytime with WooView. Simply download the <a target="_blank" href="http://www.wooviewapp.com/site/download-now/">WooView iPhone app</a>. That's it.</p>
             
             <p>View orders, change statuses, add & remove order notes, and contact your customer with a touch. You will have access to detailed reports, native graphs, and much more.
             Have multiple WooCommerce shops? No problem, you can save them as bookmarks for easy access anytime.</p>
         </div>
      </div>
    </div>
    
    
    
    
    <!-- SUPPORT !-->
    <div class="grid grid-pad block">
      <div class="col-1-1">
         <div class="content">
            <h2>Support</h2>
            <hr/><br/>
         </div>
      </div>
      <div class="col-1-3">
         <div class="content">
          <a class="support-link" href="http://www.wooviewapp.com/site/category/frequently-asked-questions/" target="_blank">FAQs <div class="dashicons dashicons-arrow-right-alt2"></div></a><br/>
          <p>Have a question? Take a look at our frequently asked questions page.</p>
         </div>
      </div>
      <div class="col-1-3">
         <div class="content">
          <a class="support-link" href="http://www.wooviewapp.com/site/support/" target="_blank">Support <div class="dashicons dashicons-arrow-right-alt2"></div></a><br/>
          <p>Take a look at our support articles to learn more or help troubleshoot any issue you might be having.</p>
         </div>
      </div>
      <div class="col-1-3">
         <div class="content">
          <a class="support-link" href="http://www.wooviewapp.com/site/contact-us/" target="_blank">Contact Us <div class="dashicons dashicons-arrow-right-alt2"></div></a><br/>
          <p>Don't see the information you need under support or FAQs? Drop us a line.</p>
         </div>
      </div>
    </div>
    <!-- SUPPORT .END !-->
    
    <!-- COMPATIBILITY CHECK !-->
    <div class="grid grid-pad block">
      <div class="col-1-1">
         <div class="content">
            <h2>Compatibility Check</h2>
            <hr/>
            <?php $has_errors = false; ?>
            <?php if($GLOBALS['wooview_min_wp_version'] > $GLOBALS['wooview_wp_version']): $has_errors = true; ?>
              <p><strong><div class="dashicons dashicons-no-alt"></div> Error: </strong> You need to update your Wordpress installation to at least version <?= $GLOBALS['wooview_min_wp_version'] ?> or newer.</p>
            <?php endif; ?>
            <?php if(!$GLOBALS['wooview_wc_active']): $has_errors = true; ?>
              <p><strong><div class="dashicons dashicons-no-alt"></div> Error: </strong> <a href="http://www.woothemes.com/woocommerce/" target="_blank">Woocommerce</a> must be installed to use WooView.</p>
            <?php else: ?>
              <?php if($GLOBALS['wooview_min_wc_version'] > $GLOBALS['wooview_wc_version']): $has_errors = true; ?>
                <p><strong><div class="dashicons dashicons-no-alt"></div> Error: </strong> You need to update your Woocommerce installation to at least version <?= $GLOBALS['wooview_min_wc_version'] ?> or newer.</p>
              <?php endif; ?>
            <?php endif; ?>
            <?php if(!$has_errors): ?>
              <p><div class="dashicons dashicons-yes"></div> Your Wordpress and Woocommerce versions are compatible with WooView. Enjoy!</p>
            <?php endif; ?>
         </div>
      </div>
      <div class="col-1-3">
         <div class="content">
             <h3><?php echo ($GLOBALS['wooview_min_wp_version'] < $GLOBALS['wooview_wp_version']) ? '<div class="dashicons dashicons-yes"></div>' : '<div class="dashicons dashicons-no-alt"></div>'; ?>Wordpress</h3>
             <p><strong>Requirement: </strong><?= $GLOBALS['wooview_min_wp_version'] ?> or later<br/>
             <strong>Your Version: </strong><?= $GLOBALS['wooview_wp_version'] ?></p>
         </div>
      </div>
      <div class="col-1-3">
         <div class="content">
             <h3><?php echo ($GLOBALS['wooview_min_wc_version'] < $GLOBALS['wooview_wc_version']) ? '<div class="dashicons dashicons-yes"></div>' : '<div class="dashicons dashicons-no-alt"></div>'; ?>Woocommerce</h3>
             <p><strong>Requirement: </strong><?= $GLOBALS['wooview_min_wc_version'] ?> or later<br/>
             <strong>Your Version: </strong><?= $GLOBALS['wooview_wc_version'] ?></p>
         </div>
      </div>
      <div class="col-1-3">
         <div class="content">
             <h3>WooView</h3>
             <p><strong>Version: </strong><?= $GLOBALS['wooview_wv_version'] ?></p>
         </div>
      </div>
    </div>
    <!-- COMPATIBILITY CHECK .END !-->
    
    <div class="grid grid-pad block">
      <hr/><br/>
      <div class="col-4-12">
         <div class="content">
          Copyright &copy; <?php echo date('Y') ?> <a href="http://www.wooviewapp.com" target="_blank">WooViewApp.com</a>
         </div>
      </div>
      <div class="col-6-12">
         <div class="content">
          &nbsp;
         </div>
      </div>
      <div class="col-2-12">
         <div class="content" style="text-align: right;">
          <a href="http://www.bcslbrands.com" target="_blank">BCSL Brands, LLC.</a>
         </div>
      </div>
    </div>
    
    
  </div>
</div>