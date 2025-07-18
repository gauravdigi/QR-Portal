<?php

  $sql = "SELECT id, name, type, flat_no, expiry_date, photo, is_deleted FROM users WHERE is_deleted = 0 
            AND expiry_date >= CURDATE() ORDER BY id DESC";
  $result = $conn->query($sql);

 ?>


<div class="container-fluid table-container">
<h1 style="
    text-align: center;
"> Acme Jubilee Owner Welfare Association</h1>
  <div class="d-flex justify-content-end align-items-center mb-3">

    <div class="d-flex justify-content-between align-items-center mb-3 gap-3 gqrcodeBtn">
      <button class="btn btn-sm btn-outline-primary grcodePopup">
        <i class="bi bi-qr-code-scan me-2"></i>Generate QRcode
      </button>

      <div class="text-end">
        <a href="?logout=1" class="btn btn-sm btn-outline-danger text-decoration-none">
          <i class="bi bi-box-arrow-right me-1"></i>Logout
        </a>
      </div> 
    </div>
  </div>


<!-- Move this outside the DataTable -->

  <div class="table-toolbar mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div id="customFilterWrapper" class="d-inline-flex align-items-center gap-2 d-none">
      <label for="statusFilter" class="mb-0">Status:</label>
      <select id="statusFilter" class="form-select form-select-sm w-auto">
        <option value="all">All</option>
        <option value="active">Active</option>
        <option value="deleted">Deleted</option>
        <option value="expired">Expired</option>
      </select>
    </div>
  </div>

  <table id="userTable" class="table table-striped table-bordered">
    <thead>
      <tr>
        <th class="no-sort">Photo</th>
        <th>Name</th>
        <th>Member Type</th>
        <th class="flat_no">Flat No</th>
        <th>Expiry Date</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
            <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <?php
              // Determine user status
              $status = '';
              $statusClass = '';

              if ($row['is_deleted']) {
                  $status = 'Deleted';
                  $statusClass = 'text-danger';
              } elseif (strtotime($row['expiry_date']) < strtotime(date('Y-m-d'))) {
                  $status = 'Expired';
                  $statusClass = 'text-warning';
              } else {
                  $status = 'Active';
                  $statusClass = 'text-success';
              }
            ?>
          <tr>
            <td><img src="uploads/<?= htmlspecialchars($row['photo']) ?>" class="avatar" alt="User Photo"></td>
            <td><span title="<?= htmlspecialchars(ucwords($row['name'])) ?>"><?= htmlspecialchars(ucwords($row['name'])) ?></span></td>
            <td><?= htmlspecialchars(ucwords($row['type'])) ?></td>
            <td class="flat_no"><?= htmlspecialchars($row['flat_no']) ?></td>
            <td><?= htmlspecialchars(date('j F Y', strtotime($row['expiry_date']))) ?></td>
            <td class="<?= $statusClass ?>"><?= $status ?></td>
            <td class='actionTd'>
              <button class="btn btn-sm btn-outline-warning editExpiryBtn"
                    data-id="<?= $row['id']; ?>"
                    data-expiry="<?= $row['expiry_date']; ?>" data-flatno="<?php echo $row['flat_no']; ?>" >
             <i class="bi bi-pencil-square"></i> Edit
            </button>
              <button class="btn btn-sm btn-outline-info viewuser" data-id="<?= $row['id'] ?>">
                <i class="bi bi-eye-fill"></i>View
              </button>
              <button class="btn btn-sm btn-outline-danger deletebtn" data-id="<?= $row['id'] ?>">
                <i class="bi bi-trash3-fill"></i>Delete
              </button>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
        <td class='text-center'></td>
        <td class='text-center'></td>
        <td class='text-center'></td>
        <td class='text-center'>No records found</td>
        <td class='text-center'></td>
        <td class='text-center'></td>
        <td class='text-center'></td>
    </tr>
      <?php endif; ?>
    </tbody>
    <tfoot>
      <tr>
        <th class="no-sort">Photo</th>
        <th>Name</th>
        <th>Member Type</th>
        <th class="flat_no">Flat No</th>
        <th>Expiry Date</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
    </tfoot>
  </table>
</div>


<!-- Generate QRcode popup -->

<div class="modal fade" id="gqrcodeModal" tabindex="-1" aria-labelledby="gqrcodeLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content shadow">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold gqrcode" ><i class="bi bi-qr-code-scan me-2"></i> Generate QR Code</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
        <div class="modal-body">
            <form id="qrcodeForm" action="generate_qrcode.php" method="POST" enctype="multipart/form-data" novalidate>
              <!-- Photo Upload -->
              <div class="mb-3">
                <label for="photo" class="form-label fw-semibold">Photo <span class="text-danger">*</span></label>
                <input type="file" class="form-control" id="photo" name="photo" accept=".jpg, .jpeg, .png, .gif" required>
                <div class="invalid-feedback">Please upload a photo (JPG, PNG, GIF).</div>
              </div>

              <!-- Name -->
              <div class="mb-3">
                <label for="name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Enter full name" required autocomplete="off">
                <div class="invalid-feedback">Name is required.</div>
              </div>

              <!-- Member Type -->
              <div class="mb-3">
                <label for="type" class="form-label fw-semibold">Member Type <span class="text-danger">*</span></label>
                <select class="form-select" id="type" name="type" aria-label="Select Type" required>
                  <option value="" selected>Select..</option>
                  <option value="Tenant">Tenant </option>
                  <option value="Owner">Owner</option>
                  <option value="Guest">Guest</option>
                </select>
                <div class="invalid-feedback type-error">Please select a valid option.</div>
              </div>

              <!-- Flat No -->
              <div class="mb-3">
                <label for="flat_no" class="form-label fw-semibold">Flat No. <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="flat_no" name="flat_no"
                       placeholder="E.g., T1-101" pattern="^T([1-9]|1[0-2])-\d{3,4}$" required value="T" autocomplete="off">
                <div class="invalid-feedback flat-no-error">
                  Format must be like T1-101 or T12-1001.
                </div>
              </div>

              <!-- Expiry Date -->
              <div class="mb-3">
                <label for="expiry_date" class="form-label fw-semibold">Expiry Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="expiry_date" name="expiry_date" required placeholder="YYYY-MM-DD" autocomplete="off" onfocus="this.removeAttribute('readonly');" readonly disabled>
                <div class="invalid-feedback">Expiry date is required.</div>
              </div>

              <!-- Submit Button -->
              <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">
                  <i class="bi bi-check-circle-fill me-2"></i>Generate QR
                </button>
              </div>
            </form>

      

            <!-- Response -->
            <div id="responseContainer" class="mt-4"></div>
        </div>
      </div>
    </div>
  </div>


<!-- Modal for QR Code Output -->
<div class="modal fade" id="qrCodeModal" tabindex="-1" aria-labelledby="qrCodeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="qrCodeModalLabel"><i class="bi bi-shield-lock-fill me-2"></i>Your Secure QR Code</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="qrCodeResult">
        <!-- Result will be injected here -->
      </div>
    </div>
  </div>
</div>

<!-- Edit Exipry date -->
<div class="modal fade editExpiryModal" id="editExpiryModal" tabindex="-1" aria-labelledby="editExpiryLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg rounded-4 border-0">
      
      <!-- Modal Header -->
      <div class="modal-header bg-primary text-white rounded-top-4 px-4">
        <h5 class="modal-title fw-bold">Update Expiry Date</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <!-- Modal Body -->
      <div class="modal-body text-center py-4 px-4">
        
        <!-- Profile Photo -->
        <div class="profile-photo-container">
          <img id="viewUserPhotoexp" src="" alt="User Photo">
          <a id="downloadPhotoLinkexp" href="#" download class="download-icon">
            <i class="bi bi-download fs-5"></i>
          </a>
        </div>

        <!-- User Info -->
        <div class="user-info text-start mx-auto mb-4" style="max-width: 400px;">
          <p><strong>Name:</strong> <span id="viewUserNameexp"></span></p>
          <p><strong>Member Type:</strong> <span id="viewUserTypeexp"></span></p>
          <p><strong>Flat No.:</strong> <span id="viewUserFlatexp"></span></p>
          
        </div>
     

        <!-- Form Start -->
        <form id="updateExpiryForm" method="post">
          <input type="hidden" name="user_id" id="edit_user_id">
          <input type="hidden" name="flat-no" id="flatno_id">
          <input type="hidden" name="type" id="type_user">
          <div class="form-floating mb-4 mx-auto" style="max-width: 400px;">
            <input class="form-control" type="date" id="new_expiry_date" name="new_expiry_date" placeholder="YYYY-MM-DD" required>
            <label for="new_expiry_date">New Expiry Date</label>
          </div>

          <!-- Footer Buttons -->
          <div class="modal-footer border-0 d-flex justify-content-center gap-3">
            <button type="button" class="btn btn-success px-4 edituser rounded-pill shadow-sm">
              <i class="bi bi-check-circle me-1"></i> Update
            </button>
            <button type="button" class="btn btn-secondary px-4 rounded-pill shadow-sm" data-bs-dismiss="modal">
              <i class="bi bi-x-circle me-1"></i> Cancel
            </button>
          </div>
        </form>
        <!-- Form End -->

      </div>
    </div>
  </div>
</div>


<!-- View User Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow">
      <div class="modal-header">
        <h5 class="modal-title" id="viewUserModalLabel">User Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body text-center">
        <!-- Profile Image with Download -->
        <div class="profile-photo-container">
          <img id="viewUserPhoto" src="" alt="User Photo">
          <a id="downloadPhotoLink" href="#" download class="download-icon">
            <i class="bi bi-download fs-5"></i>
          </a>
        </div>

        <!-- User Info -->
        <div class="user-info text-start mx-auto mb-4" style="max-width: 400px;">
          <p><strong>Name:</strong> <span id="viewUserName"></span></p>
          <p><strong>Member Type:</strong> <span id="viewUserType"></span></p>
          <p><strong>Flat No.:</strong> <span id="viewUserFlat"></span></p>
          <p><strong>Expiry Date:</strong> <span id="viewUserExpiry"></span></p>
        </div>

        <!-- QR Download -->
        <div class="d-flex justify-content-center">
          <a id="downloadQRLink" href="#" download class="btn btn-outline-primary">
            <i class="bi bi-qr-code me-1"></i> Download QR Code
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  th.no-sort::after {
    display: none !important;
}
th.no-sort::before {
    display: none !important;
}

</style>