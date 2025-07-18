
  flatpickr("#expiry_date, #new_expiry_date", {
    minDate: "today", // Disable past dates
    dateFormat: "Y-m-d", // Match the format (optional)
    allowInput: true
  });


function checkDate(flatNo){
      if (flatNo.length > 2) {
        $.ajax({
            url: 'get_expiry.php',
            method: 'POST',
            data: { flat_no: flatNo },
            dataType: 'json',
            success: function (response) {
                if (response.success && response.expiry_date) {
                    $('#expiry_date')
                        .val(response.expiry_date)
                        .prop('disabled', false);
                } else {
                    $('#expiry_date')
                        .val('')
                        .prop('disabled', false); // allow manual input
                }
            }
        });
    } else {
        $('#expiry_date').val('').prop('disabled', true);
    }
}

// Always start Flat No. input with 'T' and protect it
const flatNoInput = $('#flat_no');
// Prevent deleting or changing the "T" prefix
flatNoInput.on('keydown', function (e) {
  if ((this.selectionStart === 0 || this.selectionStart === 1) && e.key === "Backspace") {
    e.preventDefault();
  }
});

// Ensure input always starts with "T"
flatNoInput.on('input', function () {
  if (!this.value.startsWith('T')) {
    this.value = 'T';
  }

  // Live validation
  validateFlatNoLive();
});

flatNoInput.on('blur', function () {
    let flatNo = $(this).val().trim();
    let type = $('#type').val(); // assuming this is your select or hidden field for type

    // Reset to "T" if empty
    if (flatNo === "") {
        this.value = "T";
    }

    // If type is Guest, set expiry date to today + 4 days and disable
    if (type.toLowerCase() === "guest") {
        let today = new Date();
        today.setDate(today.getDate() + 4);

        let yyyy = today.getFullYear();
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let dd = String(today.getDate()).padStart(2, '0');
        let futureDate = `${yyyy}-${mm}-${dd}`;

        $('#expiry_date')
            .val(futureDate)
            .prop('disabled', true);
    } else {
      checkDate(flatNo);
    }
});



// Real-time validator for Flat No.
function validateFlatNoLive() {
  const flatRegex = /^T(1[0-2]|[1-9])-\d{3,4}$/;
  const flatNoVal = flatNoInput.val().trim();
  const errorDiv = $('.flat-no-error');

  if (flatNoVal.length > 1 && !flatRegex.test(flatNoVal)) {
    flatNoInput.addClass('is-invalid');
    errorDiv.text('Flat No. must be in the format T1-123 or T12-1001.');
  } else {
    flatNoInput.removeClass('is-invalid');
    errorDiv.text('');
  }
}

// Final validation on form submit
function validateForm() {
  let isValid = true;


  $('#qrcodeForm input[required]').each(function () {
    const input = $(this);
    input.removeClass('is-invalid');

    if (!input.val().trim()) {
      input.addClass('is-invalid');

      if (input.attr('id') === 'flat_no') {
        $('.flat-no-error').text('Flat No. is required.');
      }

      isValid = false;
    }
  });

  const flatNoVal = flatNoInput.val().trim();
  const flatRegex = /^T(1[0-2]|[1-9])-\d{3,4}$/;

  if (flatNoVal !== '' && !flatRegex.test(flatNoVal)) {
    flatNoInput.addClass('is-invalid');
    $('.flat-no-error').text('Flat No. must be in the format T1-123 or T12-1001.');
    isValid = false;
  } else if (flatRegex.test(flatNoVal)) {
    flatNoInput.removeClass('is-invalid');
    $('.flat-no-error').text('');
  }

  return isValid;
}



$('#photo').on('change', function () {
  const file = this.files[0];
  if (file) {
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    const maxSizeMB = 1; // Max 1 MB
    const maxSizeBytes = maxSizeMB * 1024 * 1024;

    if (!allowedTypes.includes(file.type)) {
      swal({
          title: "Warning!",
          text: "Only JPG, JPEG, PNG, and GIF files are allowed.",
          type: "warning"
        });
      $(this).val(''); // Clear the input
      return;
    }

    if (file.size > maxSizeBytes) {
      swal({
          title: "Warning!",
          text: "Image size must be less than 1 MB.",
          type: "warning"
        });
      //alert('Image size must be less than 2 MB.');
      $(this).val(''); // Clear the input
      return;
    }
  }
});

jQuery('#type').on('change', function(){
  var $typeval = jQuery(this).val();
  var flatNo = jQuery('#flat_no').val().trim();

  if($typeval == 'Guest'){

  let today = new Date();
  today.setDate(today.getDate() + 4);

  let yyyy = today.getFullYear();
  let mm = String(today.getMonth() + 1).padStart(2, '0');
  let dd = String(today.getDate()).padStart(2, '0');
  let futureDate = `${yyyy}-${mm}-${dd}`;

   $('#expiry_date')
          .val(futureDate)
          .prop('disabled', true);
  }else{
    checkDate(flatNo);
    
  }
});

$(document).ready(function () {

  $(document).on('submit', '#qrcodeForm', function (e) {
    e.preventDefault(); // Stop default form submit

    if (!validateForm()) {
      return; // Client-side validation failed
    }

    const form = this;
    const formData = new FormData(form);

    jQuery('.ApWait').show();

    // Submit form directly to generate_qrcode.php
    $.ajax({
      url: 'generate_qrcode.php',
      type: 'POST',
      data: formData,
      contentType: false,
      processData: false,
      dataType: 'json', // Expecting JSON response
      success: function (qrResponse) {
        jQuery('.ApWait').hide();
        $('#gqrcodeModal').modal('hide');

        if (qrResponse.status === 'success') {
          $('#qrCodeResult').html(qrResponse.html);

            const qrModal = new bootstrap.Modal(document.getElementById('qrCodeModal'));
            qrModal.show();
            form.reset();
        } else {
          swal({
            title: "Error!",
            text: qrResponse.message,
            type: "error"
          });
        }
      },
      error: function (xhr, status, error) {
        jQuery('.ApWait').hide();

        swal({
          title: "Error!",
          text: "Something went wrong while generating the QR code.",
          type: "error"
        });

        // Log error
        $.post('error_handler.php', {
          error_message: error,
          error_file: 'admin.js',
          error_type: 'AJAX Error'
        });
      }
    });
  });
});




// New Edit Exipry date
// $(document).on('click', '.editExpiryBtn', function () {
//   const id = $(this).data('id');
//   const expiry = $(this).data('expiry');

//   $('#edit_user_id').val(id);
//   $('#new_expiry_date').val(expiry);
//   $('#editExpiryModal').modal('show');
// });


$(document).ready(function () {
  $(document).on('click', '.editExpiryBtn', function () {
    const userId = $(this).data('id');

    $.ajax({
      url: 'get_user.php',
      type: 'POST',
      data: { id: userId },
      dataType: 'json',
      success: function (response) {
        console.log(response);
        if (response.status === 'success') {
          // Fill modal fields
          $('#viewUserPhotoexp').attr('src', response.photo);
          $('#viewUserNameexp').text(response.name);
          $('#viewUserTypeexp').text(response.type);
          $('#viewUserFlatexp').text(response.flat_no);
          $('#flatno_id').val(response.flat_no);
          $('#type_user').val(response.type);
          $('#edit_user_id').val(userId);
          $('#new_expiry_date').val(response.expiry_date);
          if(response.type == 'Guest'){
            $('#new_expiry_date').prop('disabled', true);
          }else{
             $('#new_expiry_date').prop('disabled', false);
          }
          // Set download link
          $('#downloadPhotoLinkexp').attr('href', response.photo);

          // Show modal
          $('#editExpiryModal').modal('show');
        } else {
          swal('Error!', response.message || 'Could not fetch user data.', 'error');
        }
      },
      error: function (xhr, status, error) {
        swal('Error!', 'AJAX error occurred.', 'error');
        $.post('error_handler.php', {
          error_message: error,
          error_file: 'admin.js', // Change if your JS filename is different
          error_type: 'AJAX Error'
        });
      }
    });
  });
});



$('.edituser').on('click', function (e) {
  e.preventDefault();
  var userid = jQuery('#updateExpiryForm #edit_user_id').val();
  var new_expiry_date = jQuery('#new_expiry_date').val();
  var flatno_id = jQuery('#flatno_id').val();
  jQuery('.ApWait').show();
  const flatNo = $('#flatno_id').val();

          // Step 2: Proceed to submit form via AJAX to generate QR
            $.ajax({
              url: 'update.php',
              type: 'POST',
              data: {user_id: userid, new_expiry_date: new_expiry_date,flat_no: flatno_id}, 
              dataType: 'json',
              success: function (response) {
                if(response.success){
                  jQuery('.ApWait').hide();

                  $('#editExpiryModal').modal('hide');

                    swal({
                         title: "success", 
                         text: response.message, 
                         type: "success"
                       },
                     function(){ 
                         location.reload();
                     }
                  ); 
                }else{
                  jQuery('.ApWait').hide();
                    swal({
                         title: "Error", 
                         text: response.message, 
                         type: "error"
                       },
                     function(){ 
                         location.reload();
                     }); 
                }

                
              },
              error: function (xhr, status, error) {
                jQuery('.ApWait').hide();
                swal(
                  'Error!',
                  'Error updating expiry date.',
                  'error'
                );
                $.post('error_handler.php', {
                    error_message: error,
                    error_file: 'admin.js', // Change if your JS filename is different
                    error_type: 'AJAX Error'
                  });
              }
            });
        });
// Generate code modal open
$(document).on('click', '.grcodePopup', function () {
  $('#gqrcodeModal').modal('show');
});


// Soft Delete user data
$(document).on('click', '.deletebtn', function () {
    let userId = $(this).data('id');

        $.ajax({
            url: 'delete_user.php',
            method: 'POST',
            data: { id: userId },
            success: function (response) {
                swal({
                    title: "Are you sure?",
                    text: "You want to Delete this user.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: '#DD6B55',
                    confirmButtonText: 'Yes, delete it!',
                    closeOnConfirm: false,
                    //closeOnCancel: false
                  },
                  function(){
                    swal({
                       title: "Deleted!", 
                       text: 'User deleted successfully!', 
                       type: "success"
                     },
                     function(){ 
                         location.reload();
                     }
                     );
                    
                  });
               
                
            },
            error: function (xhr, status, error) {
               swal(
                  'Error!',
                  'Failed to delete user.',
                  'error'
                );

               $.post('error_handler.php', {
                  error_message: error,
                  error_file: 'admin.js', // Change if your JS filename is different
                  error_type: 'AJAX Error'
                });
               
            }
        });
    });


$(document).ready(function(){
      $(document).on('click', '.viewuser', function(){
          var userId = $(this).data('id');

          $.ajax({
              url: 'get_user.php',
              type: 'POST',
              data: { id: userId },
              dataType: 'json',
              success: function(response){
                  if (response.status === 'success') {
                      $('#viewUserPhoto').attr('src', response.photo);
                      $('#viewUserName').text(response.name);
                      $('#viewUserType').text(response.type);
                      $('#viewUserFlat').text(response.flat_no);
                      $('#viewUserExpiry').text(response.expiry_date);

                      // Set download links
                      $('#downloadPhotoLink').attr('href', response.photo);
                      //$('#downloadQRLink').attr('href', response.qr_code);
                      if (response.qr_name) {
                          $('#downloadQRLink')
                              .attr('href', response.qr_code)
                              .show(); // or .removeClass('d-none') if you're using Bootstrap
                      } else {
                          $('#downloadQRLink')
                              .removeAttr('href')
                              .hide(); // or .addClass('d-none') to hide it
                      }
                     
                      // Show the modal
                      $('#viewUserModal').modal('show');
                  } else {
                    swal(
                      'Error!',
                      response.message,
                      'error'
                    );
                      //alert(response.message || 'Failed to fetch user data.');
                  }
              },
              error: function(xhr, status, error){
                swal(
                    'Error!',
                    'AJAX error occurred.',
                    'error'
                  );

                $.post('error_handler.php', {
                  error_message: error,
                  error_file: 'admin.js', // Change if your JS filename is different
                  error_type: 'AJAX Error'
                });
              }
          });
      });

    
  });




jQuery(document).ready(function () {
  $(document).on('click', '.Restorebtn', function () {
    const userId = $(this).data('id');

      $.ajax({
        url: 'restore_user.php',
        type: 'POST',
        data: { id: userId },
        success: function (response) {
          swal({
                 title: "Success!", 
                 text: 'User restored successfully!', 
                 type: "success"
               },
                 function(){ 
                     location.reload();
                 }
               );
        
          // Example: reload user list
          $('#statusFilter').trigger('change');
        },
        error: function (xhr, status, error) {
          swal(
                'Error!',
                'Failed to restore user.',
                'error'
              );

          $.post('error_handler.php', {
                  error_message: error,
                  error_file: 'admin.js', // Change if your JS filename is different
                  error_type: 'AJAX Error'
                });
          
        }
      });
    
  });
});



let dataTable;

$.fn.dataTable.ext.type.order['flat-no-pre'] = function (data) {
    // Expects data like: T1-101, T11-109
    if (!data) return '';
    let parts = data.split('-');
    let block = parts[0].replace(/\D/g, ''); // e.g., "T1" -> "1"
    let flat = parts[1] ? parseInt(parts[1]) : 0;
    block = parseInt(block) || 0;
    // Combine block and flat padded for stable sorting
    return block * 1000000 + flat;
};



$(document).ready(function () {
  $.fn.dataTable.moment('D MMMM YYYY');
  // Initialize DataTable immediately on empty table
  dataTable = $('#userTable').DataTable({
    dom:
      "<'row mb-2 align-items-center'<'col-md-3'l><'col-md-6'<'statusFilterWrap'>><'col-md-3'f>>" +
      "t" +
      "<'row mt-2'<'col-sm-12 col-md-6'i><'col-sm-12 col-md-6'p>>",
    pageLength: 15,
    lengthMenu: [15, 30, 50, 100],
    ordering: true,
    searching: true,
    columnDefs: [
        { orderable: false, targets: [0, 2, 5, 6] },
        { type: 'flat-no', targets: $('.flat_no').index() } // Apply custom sorting to Flat No
    ],
    //order: [[ $('.flat_no').index(), 'asc' ]] // Optional: default sort by Flat No
  });

  // Move filter to center on initial load
  const filterClone = $('#customFilterWrapper').clone(true).removeClass('d-none');
  $('.statusFilterWrap').html(filterClone);

  // Initial load with "active"
   $('.ApWait').show();
  loadUsers('active');

  // Handle status filter change
  $(document).on('change', '#statusFilter', function () {
    const filter = $(this).val();
    $('.ApWait').show();
    loadUsers(filter);
  });

function loadUsers(filter) {
  $.get('load_users.php', { filter }, function (data) {
    $('.ApWait').hide();

    // Parse HTML string to extract <tr> elements safely
    const tempDiv = $('<div>').html(data);
    const rows = tempDiv.find('tr');

    if ($.fn.DataTable.isDataTable('#userTable')) {
      // Clear existing data only (not destroy)
      dataTable.clear().rows.add(rows).draw();
    } else {
      // First time initialization
      dataTable = $('#userTable').DataTable({
        dom:
          "<'row mb-2 align-items-center'<'col-md-3'l><'col-md-6'<'statusFilterWrap'>><'col-md-3'f>>" +
          "t" +
          "<'row mt-2'<'col-sm-12 col-md-6'i><'col-sm-12 col-md-6'p>>",
        pageLength: 15,
        lengthMenu: [15, 30, 50, 100],
        ordering: true,
        searching: true,
        language: {
          emptyTable: "No data found for this filter"
        }
      });

      dataTable.rows.add(rows).draw();
    }

    // Reset the filter dropdown after reload
    const filterClone = $('#customFilterWrapper').clone(true).removeClass('d-none');
    filterClone.find('#statusFilter').val(filter);
    $('.statusFilterWrap').html(filterClone);

  }).fail(function (xhr, status, error) {
    $('.ApWait').hide();
    swal({
      title: "Load Error!",
      text: "Could not load user data. Please try again.",
      type: "error"
    });

    $.post('error_handler.php', {
      error_message: error,
      error_file: 'admin.js',
      error_type: 'AJAX Load Users Error'
    });
  });
}

$('#qrCodeModal').on('hidden.bs.modal', function () {
  //const currentFilter = $('#statusFilter').val(); // or use correct selector
  const currentFilter = $('.statusFilterWrap select').val(); // adjust selector if needed
  console.log(currentFilter);
  if (currentFilter) {
    loadUsers(currentFilter);
  } else {
    loadUsers('active'); // fallback
  }
});
$('.ApWait').hide();
});


// jQuery(document).ready(function(){
//       $('#exportBtn').on('click', function () {
//       const status = $('.statusFilterWrap select').val(); // get selected filter
//       //console.log(status);
//       $.ajax({
//           url: 'export.php',
//           method: 'POST',
//           data: { status: status },
//           xhrFields: { responseType: 'blob' }, // required to handle download
//           success: function (response, status, xhr) {
//               const disposition = xhr.getResponseHeader('Content-Disposition');
//               let filename = 'users_export.csv';
//               if (disposition && disposition.includes('filename=')) {
//                   const match = disposition.match(/filename="?(.+)"?/);
//                   if (match[1]) filename = match[1];
//               }

//               const url = window.URL.createObjectURL(response);
//               const a = document.createElement('a');
//               a.href = url;
//               a.download = filename;
//               document.body.appendChild(a);
//               a.click();
//               a.remove();
//               window.URL.revokeObjectURL(url);
//           },
//           error: function () {
//               alert('Failed to export data.');
//           }
//       });
//   });
// });


jQuery(document).ready(function(){
  $('#gqrcodeModal').on('hidden.bs.modal', function () {
    $('#qrcodeForm')[0].reset();        // Reset form fields
    $('#qrcodeForm').find('.is-invalid').removeClass('is-invalid'); // Remove validation classes
    $('#qrcodeForm').find('.text-danger').text(''); // Clear validation messages (if any)
  });
})