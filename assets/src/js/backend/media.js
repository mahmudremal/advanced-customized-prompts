const mediaImages = {
  mediaUploader: false,
  uploadImages: (data, thisClass) => {
    data.forEach(function(item) {
      mediaImages.renderImage(item.image, thisClass);
      mediaImages.renderImage(item.thumb, thisClass);
    });
  },
  renderImage: (imageURL, thisClass) => {
    var image = new Image();
    image.crossOrigin = "Anonymous";
    image.onload = function() {
    var canvas = document.createElement("canvas");
    canvas.width = this.width;
    canvas.height = this.height;

    var context = canvas.getContext("2d");
    context.drawImage(this, 0, 0);

    var imageData = canvas.toDataURL("image/jpeg");
    var blobData = mediaImages.dataURLToBlob(imageData, thisClass);

    var image = imageURL.split('/');

    // Upload the blob data to the server
    mediaImages.uploadBlobData(blobData, image[(arr.length-1)], thisClass);
    };
    image.src = imageURL;
  },
  dataURLToBlob: (dataURL, thisClass) => {
    var parts = dataURL.split(",");
    var contentType = parts[0].match(/:(.*?);/)[1];
    var raw = window.atob(parts[1]);
    var rawLength = raw.length;
    var uInt8Array = new Uint8Array(rawLength);
  
    for (var i = 0; i < rawLength; ++i) {
      uInt8Array[i] = raw.charCodeAt(i);
    }
  
    return new Blob([uInt8Array], { type: contentType });
  },
  uploadBlobData: (blobData, imageName, thisClass) => {
    var formdata = new FormData();
    formdata.append('action', 'sospopsproject/datastore/import_image_from_blob');
    formdata.append('_nonce', thisClass.ajaxNonce);
    formdata.append('image', blobData, imageName);
    formdata.append('imageName', imageName);

    thisClass.sendToServer(formdata);
  },
  uploadTexToImage: (thisClass) => {
    const button = document.querySelector('#texonomy_featured_image');
    if(!button) {return;}const $ = jQuery;
    button.addEventListener('click', (event) => {
      event.preventDefault();
      if(mediaImages.mediaUploader) {
        mediaImages.mediaUploader.open();
        return;
      }
      mediaImages.mediaUploader = wp.media.frames.file_frame = wp.media({
        title: thisClass.i18n?.chooseimage??'Choose Image',
        button: {
          text: thisClass.i18n?.chooseimage??'Choose Image'
        }, multiple: false
      });
      mediaImages.mediaUploader.on('select', function() {
        var attachment = mediaImages.mediaUploader.state().get('selection').first().toJSON();
        console.log(attachment);
        var idInput = document.querySelector('input[name="texonomy_featured_image"]');
        if(idInput) {idInput.value = attachment.id;}
        var prevImage = document.querySelector('img[data-handle="texonomy_featured_image"]');
        if(prevImage) {prevImage.src = attachment.url;}
      });
      mediaImages.mediaUploader.open();
    });
  }
};
export default mediaImages;