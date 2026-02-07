<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content rounded-3">

      <div class="modal-header border-0">
        <h5 class="modal-title fw-semibold">Confirm Logout</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body text-center">
        <p class="mb-0">Are you sure you want to logout?</p>
      </div>

      <div class="modal-footer border-0 justify-content-center">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                data-bs-dismiss="modal">
            No
        </button>

        <a href="logout.php" class="btn btn-danger rounded-pill px-4">
            Yes, Logout
        </a>
      </div>

    </div>
  </div>
</div>

<footer class="text-center text-muted py-3">
    © <?= date('Y'); ?> Shoe Shop Admin Panel
</footer>
