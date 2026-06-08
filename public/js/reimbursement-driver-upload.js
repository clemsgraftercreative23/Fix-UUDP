window.DriverUpload = (function () {
  var MAX_WIDTH = 1280;
  var MAX_HEIGHT = 1280;
  var JPEG_QUALITY = 0.82;

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

  return {
    compressImageFile: compressImageFile,
    setFileOnInput: setFileOnInput,
    captureFromVideo: captureFromVideo,
    getCameraConstraints: getCameraConstraints
  };
})();
