/**
 * Multi-attachment upload for travel reimbursement detail rows.
 * Appends files without clearing existing server-side attachments.
 */
window.TravelUpload = (function () {
  var MAX_WIDTH = 1280;
  var MAX_HEIGHT = 1280;
  var JPEG_QUALITY = 0.82;
  var PDF_ICON = 'https://cdn-icons-png.flaticon.com/512/337/337946.png';
  var ACCEPT_TYPES = 'image/*,.pdf,application/pdf';
  var PANE = '#rt-travel-item-pane';

  function scaleDimensions(width, height) {
    var w = width;
    var h = height;

    if (w > MAX_WIDTH) {
      h = Math.round(h * (MAX_WIDTH / w));
      w = MAX_WIDTH;
    }
    if (h > MAX_HEIGHT) {
      w = Math.round(w * (MAX_HEIGHT / h));
      h = MAX_HEIGHT;
    }

    return { width: w, height: h };
  }

  function setFileOnInput(input, file) {
    if (!input || !file) {
      return;
    }

    var dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
  }

  function compressImageFile(file) {
    return new Promise(function (resolve) {
      if (!file || !file.type || file.type.indexOf('image/') !== 0) {
        resolve(file);
        return;
      }

      var reader = new FileReader();
      reader.onload = function (e) {
        var img = new Image();
        img.onload = function () {
          var dims = scaleDimensions(img.width, img.height);
          var canvas = document.createElement('canvas');
          canvas.width = dims.width;
          canvas.height = dims.height;

          var ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0, dims.width, dims.height);

          canvas.toBlob(function (blob) {
            if (!blob) {
              resolve(file);
              return;
            }

            var baseName = (file.name || 'upload').replace(/\.[^.]+$/, '');
            resolve(new File([blob], baseName + '.jpg', {
              type: 'image/jpeg',
              lastModified: Date.now()
            }));
          }, 'image/jpeg', JPEG_QUALITY);
        };
        img.onerror = function () {
          resolve(file);
        };
        img.src = e.target.result;
      };
      reader.onerror = function () {
        resolve(file);
      };
      reader.readAsDataURL(file);
    });
  }

  function captureFromVideo(videoElement) {
    return new Promise(function (resolve, reject) {
      var vw = videoElement.videoWidth || 1280;
      var vh = videoElement.videoHeight || 720;
      var dims = scaleDimensions(vw, vh);
      var canvas = document.createElement('canvas');
      canvas.width = dims.width;
      canvas.height = dims.height;

      var ctx = canvas.getContext('2d');
      ctx.drawImage(videoElement, 0, 0, dims.width, dims.height);

      canvas.toBlob(function (blob) {
        if (!blob) {
          reject(new Error('Failed to capture image'));
          return;
        }

        resolve(new File([blob], 'capture.jpg', {
          type: 'image/jpeg',
          lastModified: Date.now()
        }));
      }, 'image/jpeg', JPEG_QUALITY);
    });
  }

  function getCameraConstraints() {
    return {
      video: {
        facingMode: { ideal: 'environment' },
        width: { ideal: 1280 },
        height: { ideal: 720 }
      }
    };
  }

  function getRowIndex(row) {
    var $row = $(row);
    var explicit = $row.attr('data-row-index');
    if (explicit !== undefined && explicit !== '') {
      return parseInt(explicit, 10);
    }
    var $tbody = $row.closest('tbody');
    return $tbody.find('tr.fieldGroupDetail').index($row);
  }

  function getPreviewDivFromRow(row) {
    return $(row).find('[id^="preview_"]').first();
  }

  function appendAttachmentInput(row, rowIndex, file) {
    var $row = $(row);
    var $container = $row.find('.attachment-inputs').first();
    if (!$container.length) {
      $container = $('<div class="attachment-inputs" style="display:none;"></div>');
      $row.find('.file-proof').first().append($container);
    }

    var uid = 'att_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8);
    var $input = $('<input type="file" class="pending-attachment-input">')
      .attr('name', 'attachments[' + rowIndex + '][]')
      .attr('data-uid', uid);
    setFileOnInput($input[0], file);
    $container.append($input);
    return uid;
  }

  function isPdfFile(file) {
    if (!file) {
      return false;
    }
    if (file.type === 'application/pdf') {
      return true;
    }
    var name = (file.name || '').toLowerCase();
    return name.slice(-4) === '.pdf';
  }

  function renderFilePreview(file, uid) {
    return new Promise(function (resolve) {
      var $wrap = $('<div class="pending-attachment-item" style="margin-top:6px; border:1px solid #d9d9d9; border-radius:6px; padding:6px;">')
        .attr('data-uid', uid);
      var $inner = $('<div style="display:flex; gap:6px; align-items:center;">');
      var $remove = $('<button type="button" class="btn btn-sm btn-danger remove-pending-attachment" style="margin-left:auto;">x</button>');

      if (file.type && file.type.indexOf('image/') === 0) {
        var reader = new FileReader();
        reader.onload = function (e) {
          $inner.append(
            $('<img>').attr({
              src: e.target.result,
              'data-preview-src': e.target.result
            }).addClass('preview-thumbnail').css({
              maxWidth: '55px',
              maxHeight: '55px',
              border: '2px solid #28a745',
              borderRadius: '5px',
              cursor: 'pointer'
            })
          );
          $inner.append($remove);
          $wrap.append($inner);
          resolve($wrap);
        };
        reader.readAsDataURL(file);
      } else if (isPdfFile(file)) {
        var fileURL = URL.createObjectURL(file);
        $inner.append(
          $('<a>').attr({
            href: fileURL,
            target: '_blank',
            title: 'Lihat PDF'
          }).append(
            $('<img>').attr({
              src: PDF_ICON,
              alt: 'PDF File'
            }).css({
              maxWidth: '40px',
              maxHeight: '40px',
              border: '2px solid #007bff',
              borderRadius: '5px'
            })
          )
        );
        $inner.append($('<span>').text(file.name || 'PDF').css({
          fontSize: '12px',
          maxWidth: '100px',
          overflow: 'hidden',
          textOverflow: 'ellipsis',
          whiteSpace: 'nowrap',
          display: 'inline-block'
        }));
        $inner.append($remove);
        $wrap.append($inner);
        resolve($wrap);
      } else {
        $inner.append($('<p style="color:red;">File tidak didukung</p>'));
        $inner.append($remove);
        $wrap.append($inner);
        resolve($wrap);
      }
    });
  }

  function removePendingPreview($item) {
    var uid = $item.attr('data-uid');
    if (uid) {
      $item.closest('tr').find('.pending-attachment-input[data-uid="' + uid + '"]').remove();
    }
    $item.find('a[href^="blob:"]').each(function () {
      try {
        var href = $(this).attr('href');
        if (href) {
          URL.revokeObjectURL(href);
        }
      } catch (e) { /* ignore */ }
    });
    $item.remove();
    syncUploadWarning();
  }

  function syncUploadWarning() {
    $('#action_button, #action_button_draft, #action_button_submit').prop('disabled', false);
  }

  function enableSubmitButtons() {
    $('#action_button, #action_button_draft, #action_button_submit').prop('disabled', false);
    $(PANE).find('.warning-upload').hide();
    if (typeof window.rtTravelSyncFileUploadWarning === 'function') {
      window.rtTravelSyncFileUploadWarning($(PANE));
    }
  }

  function processAndAppendFile(row, file) {
    return compressImageFile(file).then(function (processed) {
      var rowIndex = getRowIndex(row);
      var uid = appendAttachmentInput(row, rowIndex, processed);
      var previewDiv = getPreviewDivFromRow(row);
      return renderFilePreview(processed, uid).then(function ($el) {
        previewDiv.append($el);
        enableSubmitButtons();
        return processed;
      });
    });
  }

  function resetPickerInput(input) {
    if (!input) {
      return;
    }
    input.value = '';
  }

  function markUploadHandled(event) {
    if (event && event.originalEvent) {
      event.originalEvent.__rtTravelUploadHandled = true;
    }
  }

  function bindAttachmentHandlers() {
    if (window.__travelUploadBound) {
      return;
    }
    window.__travelUploadBound = true;

    $('body').on('click', PANE + ' .remove-pending-attachment', function () {
      removePendingPreview($(this).closest('.pending-attachment-item'));
    });

    $('body').on('click', PANE + ' .addFile', function () {
      var btn = $(this);
      var row = btn.closest('tr');
      var fileInput = row.find('.file-input').first();

      fileInput.click();

      fileInput.off('change.travelUpload').on('change.travelUpload', function (event) {
        markUploadHandled(event);
        var file = event.target.files[0];
        if (!file) {
          return;
        }

        processAndAppendFile(row, file).then(function () {
          btn.find('i').removeClass('fa-upload').addClass('fa-check');
          resetPickerInput(fileInput[0]);
        });
      });
    });

    $('body').on('click', PANE + ' .addCamera', function () {
      var btn = $(this);
      var row = btn.closest('tr');
      var cameraInput = row.find('.camera-input').first();

      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        return;
      }

      navigator.mediaDevices.getUserMedia(getCameraConstraints())
        .then(function (stream) {
          $('#modalPhoto').modal('show');
          var videoElement = $('#videoElement')[0];
          videoElement.srcObject = stream;

          $('#captureButton').off('click.travelUpload').on('click.travelUpload', function () {
            captureFromVideo(videoElement).then(function (file) {
              return processAndAppendFile(row, file);
            }).then(function () {
              btn.find('i').removeClass('fa-camera').addClass('fa-check');
              stream.getTracks().forEach(function (track) { track.stop(); });
              $('#modalPhoto').modal('hide');
            }).catch(function (err) {
              console.error('Failed to capture image: ' + err);
            });
          });
        })
        .catch(function (err) {
          console.error('Error accessing webcam: ' + err);
        });
    });
  }

  if (typeof jQuery !== 'undefined') {
    $(function () {
      bindAttachmentHandlers();
    });
  }

  return {
    ACCEPT_TYPES: ACCEPT_TYPES,
    compressImageFile: compressImageFile,
    setFileOnInput: setFileOnInput,
    captureFromVideo: captureFromVideo,
    getCameraConstraints: getCameraConstraints,
    getRowIndex: getRowIndex,
    getPreviewDivFromRow: getPreviewDivFromRow,
    appendAttachmentInput: appendAttachmentInput,
    renderFilePreview: renderFilePreview,
    removePendingPreview: removePendingPreview,
    processAndAppendFile: processAndAppendFile,
    enableSubmitButtons: enableSubmitButtons,
    bindAttachmentHandlers: bindAttachmentHandlers
  };
})();
