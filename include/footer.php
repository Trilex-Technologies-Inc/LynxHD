<?php $is_modern_home = !empty($modern_home); ?>
<?php if (!$is_modern_home): ?>
      </div>
    </section>
  </main>
<?php endif; ?>

  <footer class="site-footer border-top bg-white py-4 mt-auto">
    <div class="container d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 text-secondary small">
      <span>&copy; <?php echo date('Y'); ?> LynxHD</span>
      <nav aria-label="Footer navigation">
        <a class="text-decoration-none" href="<?php echo isset($asset_prefix) ? $asset_prefix : '' ?>index.php">Support home</a>
      </nav>
    </div>
  </footer>
<?php
if (isset($INSTALLED) && $INSTALLED) {
  include_once __DIR__ . '/../modules/livechat/bootstrap.php';
  livechat_render_widget(isset($asset_prefix) ? $asset_prefix : '');
}
?>
</body>
</html>
