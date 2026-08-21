<?php $admin_logged_in = $INSTALLED && (($_SESSION['login_type'] ?? $LOGIN_INVALID) != $LOGIN_INVALID); ?>
<?php if ($admin_logged_in): ?>
      </div>
    </div>
    <footer class="sticky-footer bg-white mt-4"><div class="container my-auto"><div class="copyright text-center my-auto"><span>&copy; <?php echo date('Y') ?> LynxHD</span></div></div></footer>
  </div>
</div>
<a class="scroll-to-top rounded" href="#page-top" aria-label="Back to top"><i class="fas fa-angle-up"></i></a>
<?php else: ?>
          </div></div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
<script src="./vendor/sb-admin-2/js/sb-admin-2.min.js"></script>
</body>
</html>
