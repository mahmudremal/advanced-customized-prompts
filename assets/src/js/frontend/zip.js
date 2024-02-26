class Zip {
    constructor(thisClass) {
        this.setup_hooks(thisClass);
    }
    setup_hooks(thisClass) {
        this.init_zip_picker(thisClass);
    }
    /**
     * Asks user for give her location only once.
     * Asks from a hompage button or listing list screen.
     */
    init_zip_picker(thisClass) {
        const zipClass = this;var form, html, config, json, card, node, error;
        document.querySelectorAll(zipClass.classes_zip_picker()).forEach((el)=>{
            el.dataset.handled = true;
            el.addEventListener('click', (event) => {
                event.preventDefault();
                zipClass.launch_zip_prompts(thisClass);
            });
        });
        if (thisClass.config?.showPrompts) {
            setTimeout(() => {
                zipClass.launch_zip_prompts(thisClass);
            }, 5000);
        }
    }
    classes_zip_picker() {
        var classes = ['.elementor-element-3ba3ce1 .elementor-icon-list-item', '.custom_zip_btn'];
        return classes.map((clas) => clas + ':not([data-handled])').join(', ');
    }
    zip_template(thisClass) {
        const html = `
        <p class="swal2-html-container__subtitle">${
            thisClass.i18n?.findsupersrvcinarea??'Simply Enter Your Location or Zip Code'
        }</p>
        <div class="swal2-html-container__form">
            <div class="location-picker">
                <div class="icon-container">
                    <i class="fas fa-map-marker-alt" fa-bullseye></i>
                </div>
                <div class="input-container">
                    <input type="text" class="zip-code" placeholder="${
                        thisClass.i18n?.enterzipcode??'Locate Me or Enter Your Zip Code'
                    }" value="${thisClass.config?.userzip??''}">
                </div>
                <button class="submit-button">${
                    thisClass.i18n?.findservices??'Locate Local Services'
                }</button>
            </div>
            <div class="location-locateme">
                <p class="location-locateme__text">${
                    thisClass.i18n?.or??'or'
                } <a class="locate-me" href="#">${
                    thisClass.i18n?.locateme??'Locate me'
                }</a></p>
            </div>
        </div>
        `;
        return html;
    }
    launch_zip_prompts(thisClass) {
        const zipClass = this;
        var html = zipClass.zip_template(thisClass);
        thisClass.Swal.fire({
            title: thisClass.i18n?.findsupersrvcinarea??'Discover Local Services',
            html: html, width: 600,
            showConfirmButton: false,
            showCancelButton: false,
            showCloseButton: true,
            allowOutsideClick: false,
            allowEscapeKey: true,
            // confirmButtonText: 'Generate',
            // cancelButtonText: 'Close',
            // confirmButtonColor: '#3085d6',
            // cancelButtonColor: '#d33',
            customClass: {popup: 'fwp-zip_popup'},
            // focusConfirm: true,
            // reverseButtons: true,
            // backdrop: `rgba(0,0,123,0.4) url("https://sweetalert2.github.io/images/nyan-cat.gif") left top no-repeat`,
            // backdrop: `rgb(255 255 255)`,

            showLoaderOnConfirm: true,
            allowOutsideClick: false, // () => !Swal.isLoading(),
            
            // html: html,
            // footer: '<a href="">Why do I have this issue?</a>',
            didOpen: async () => {
                const locationIcon = document.querySelector('.location-picker .icon-container');
                const locationIconFa = locationIcon.querySelector('.fas');
                const zipCodeResult = document.querySelector('.location-picker .zip-code');
                const findButton = document.querySelector('.location-picker .submit-button');
                const locateMe = document.querySelector('.location-locateme .locate-me');
                locationIcon?.addEventListener("click", (event) => {
                    event.preventDefault();
                    // Check if geolocation is supported by the browser
                    if("geolocation" in navigator) {
                        locationIconFa.classList.remove('fa-map-marker-alt');
                        locationIconFa.classList.add('fa-spinner', 'fa-spin');
                        navigator.geolocation.getCurrentPosition(async (position) => {
                            try {
                                // Get the user's latitude and longitude
                                const { latitude, longitude } = position.coords;
                                if(thisClass?.responsedZipCode) {
                                    zipCodeResult.value = thisClass?.responsedZipCode;
                                    // return;
                                }
                                const response = await fetch(
                                    `https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=10&lat=${latitude}&lon=${longitude}`
                                ).then((response) => response.json()).then((json) => {
                                    if(json?.address && json.address?.postcode) {
                                        const zipCode = json.address.postcode;
                                        thisClass.responsedZipCode = zipCode;
                                        zipCodeResult.value = zipCode;
                                    } else {
                                        throw new Error('ZIP Code not found')
                                    }
                                }).catch ((error) => {
                                    console.error(error);
                                    thisClass.toastify({text: error, style: {background: "linear-gradient(to right, rgb(222 66 75), rgb(249 144 150))"}}).showToast();
                                });
                            } catch (error) {
                                thisClass.toastify({text: error, style: {background: "linear-gradient(to right, rgb(222 66 75), rgb(249 144 150))"}}).showToast();
                                console.error(error);
                            }
                            // replacing icon removing preloader
                            locationIconFa.classList.add('fa-map-marker-alt');
                            locationIconFa.classList.remove('fa-spinner', 'fa-spin');
                        });
                    } else {
                        error = thisClass.i18n?.geonotsupport??'Geolocation is not supported by your browser';
                        thisClass.toastify({text: error, style: {background: "linear-gradient(to right, rgb(222 66 75), rgb(249 144 150))"}}).showToast();
                    }
                });
                findButton?.addEventListener("click", (event) => {
                    event.preventDefault();
                    if(zipCodeResult.value?.trim() != '') {
                        var formdata = new FormData();
                        formdata.append('action', 'sospopsproject/ajax/update/zipcode');
                        formdata.append('_zipcode', zipCodeResult.value);
                        formdata.append('_nonce', thisClass.ajaxNonce);
                        thisClass.sendToServer(formdata);
                    } else {
                        error = thisClass.i18n?.plsvalidzip??'Please input a valid zip code.';
                        thisClass.toastify({text: error, style: {background: "linear-gradient(to right, rgb(222 66 75), rgb(249 144 150))"}}).showToast();
                    }
                });
                locateMe?.addEventListener("click", (event) => {
                    locationIcon?.click();
                });
            },
            preConfirm: async (login) => {return thisClass.prompts.on_Closed(thisClass);}
        }).then(async (result) => {
            if( result.isConfirmed ) {
                if( typeof result.value === 'undefined') {
                    thisClass.notify.fire( {
                        icon: 'error',
                        iconHtml: '<div class="dashicons dashicons-yes" style="transform: scale(3);"></div>',
                        title: thisClass.i18n?.somethingwentwrong??'Something went wrong!',
                    });
                } else if( thisClass.lastReqs.content_type == 'text') {
                    // result.value.data 
                    thisClass.handle_completion();
                } else {
                    const selectedImages = await thisClass.choose_image();
                }
            }
        })
    }
}

export default Zip;