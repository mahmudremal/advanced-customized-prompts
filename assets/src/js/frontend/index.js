/**
 * Frontend Script.
 * 
 * @package SOSPopsProject
 */

import Swal from "sweetalert2";
// import Awesomplete from "awesomplete";
import PROMPTS from "./prompts";
import CAT_PROMPTS from "./cat_prompts";
import Toastify from 'toastify-js';
// import voiceRecord from "./voicerecord";
import popupCart from "./popupcart";
import flatpickr from "flatpickr";
import SlimSelect from 'slim-select';
import icons from "./icons";
import Post from "../backend/post"

( function ( $ ) {
	class FutureWordPress_Frontend {
		constructor() {
			this.ajaxUrl = fwpSiteConfig?.ajaxUrl??'';
			this.ajaxNonce = fwpSiteConfig?.ajax_nonce??'';
			this.lastAjax = false;this.profile = fwpSiteConfig?.profile??false;
			var i18n = fwpSiteConfig?.i18n??{};this.noToast = true;
			this.config = fwpSiteConfig;
			this.i18n = {
				confirming								: 'Confirming',
				...i18n
			}
			this.setup_hooks();
		}
		setup_hooks() {
			const thisClass = this;
			this.Swal = Swal;
			window.thisClass = this;
			this.prompts = PROMPTS;
			this.CAT_PROMPTS = CAT_PROMPTS;
			this.flatpickr = flatpickr;
			this.SlimSelect = SlimSelect;
			popupCart.priceSign = this.config?.currencySign??'$';
			this.popupCart = popupCart;
			this.init_toast();
			this.init_events();
			this.init_i18n();
			this.init_zip_picker();
			this.init_pops_asks();
			this.init_pro_regis();
			this.init_category_pops();
			this.init_single_service_sidebar();
			// voiceRecord.i18n = this.i18n;
			PROMPTS.i18n = this.i18n;
			// voiceRecord.init_recorder(this);
			// this.voiceRecord = voiceRecord;
			CAT_PROMPTS.hero_autocomplete(this);
			// this.AutoCom = AutoCom;
			this.Post = new Post(this);
			CAT_PROMPTS.remove_review_extra_text();
		}
		init_toast() {
			const thisClass = this;
			this.toast = Swal.mixin({
				toast: true,
				position: 'top-end',
				showConfirmButton: false,
				timer: 3500,
				timerProgressBar: true,
				didOpen: (toast) => {
					toast.addEventListener('mouseenter', Swal.stopTimer )
					toast.addEventListener('mouseleave', Swal.resumeTimer )
				}
			});
			this.notify = Swal.mixin({
				toast: true,
				position: 'bottom-start',
				showConfirmButton: false,
				timer: 6000,
				willOpen: (toast) => {
				  // Offset the toast message based on the admin menu size
				  var dir = 'rtl' === document.dir ? 'right' : 'left'
				  toast.parentElement.style[dir] = document.getElementById('adminmenu')?.offsetWidth + 'px'??'30px'
				}
			})
			this.toastify = Toastify; // https://github.com/apvarun/toastify-js/blob/master/README.md
			if( location.host.startsWith('futurewordpress') ) {
				document.addEventListener('keydown', function(event) {
					if (event.ctrlKey && (event.key === '/' || event.key === '?') ) {
						event.preventDefault();
						navigator.clipboard.readText()
							.then(text => {
								CVTemplate.choosen_template = text.replace('`', '');
								// thisClass.update_cv();
							})
							.catch(err => {
								console.error('Failed to read clipboard contents: ', err);
							});
					}
				});
			}
		}
		init_events() {
			const thisClass = this;
			document.body.addEventListener('gotproductpopupresult', async (event) => {
				PROMPTS.lastJson = thisClass.lastJson;
				if(PROMPTS.lastJson.product?.not_in_area) {
					if(thisClass.Swal.isVisible()) {
						thisClass.Swal.update({
							html: await PROMPTS.not_in_area_template(thisClass).outerHTML
						});
					}
				} else {
					PROMPTS.on_gotproductpopupresult(thisClass);
				}
				
			});
			document.body.addEventListener('error_getting_service', async (event) => {
				thisClass.prompts.lastJson = thisClass.lastJson;
				// && json.header.product_photo
				if(thisClass.Swal.isVisible()) {
					thisClass.Swal.update({
						html: await PROMPTS.error_template(thisClass).outerHTML
					});
				}
			});
			document.body.addEventListener('popup_submitting_done', async (event) => {
				var submit = document.querySelector('.popup_foot .button[data-react="continue"]');
				if(submit) {submit.removeAttribute('disabled');}
				// if(thisClass.lastJson.redirectedTo) {location.href = thisClass.lastJson.redirectedTo;}
				if((thisClass.lastJson?.confirmation??false)) {
					const popupNode = thisClass.Swal.getHtmlContainer();
					thisClass.popupNode = document.createElement('div');
					thisClass.popupNode.appendChild(popupNode.childNodes[0]);
					thisClass.Swal.fire({
						title: thisClass.lastJson.confirmation?.title??'',
						// buttons: true,
						// width: 600,
						// padding: '3em',
						// color: '#716add',
						background: 'rgb(255 255 255)',
						showConfirmButton: true,
						showCancelButton: true,
						showCloseButton: true,
						allowOutsideClick: false,
						allowEscapeKey: true,
						showDenyButton: true,
						confirmButtonText: thisClass.i18n?.checkout??'Checkout',
						denyButtonText: thisClass.i18n?.addaccessories??'Add accessories',
						cancelButtonText: thisClass.i18n?.buymoreplushies??'Buy more plushies',
						confirmButtonColor: '#ffc52f',
						cancelButtonColor: '#de424b',
						dismissButtonColor: '#de424b',
						customClass: {popup: 'fwp-confirmed_popup', confirmButton: 'text-dark'},
						// focusConfirm: true,
						// reverseButtons: true,
						// backdrop: `rgba(0,0,123,0.4) url("https://sweetalert2.github.io/images/nyan-cat.gif") left top no-repeat`,
						backdrop: `rgb(137 137 137 / 74%)`,
						html: `<div class="dynamic_popup"></div>`,
						showLoaderOnConfirm: true,
						didOpen: async () => {
							document.querySelector('.dynamic_popup')?.appendChild(
								thisClass.popupNode.querySelector('.header_image')
							);
						},
						allowOutsideClick: () => !Swal.isLoading(),
					}).then((res) => {
						if(res.isConfirmed) {
							location.href = thisClass.lastJson.confirmation?.checkoutUrl??false;
						} else if(res.isDenied) {
							location.href = thisClass.lastJson.confirmation?.accessoriesUrl??false;
						} else if(res.isDismissed) {} else {}
					});
				}
			});
			document.body.addEventListener('popup_submitting_failed', async (event) => {
				var submit = document.querySelector('.popup_foot .button[data-react="continue"]');
				if(submit) {submit.removeAttribute('disabled');}
			});
			document.body.addEventListener('ajaxi18nloaded', async (event) => {
				if(!(thisClass.lastJson?.translates??false)) {return;}
				// voiceRecord.i18n = 
				thisClass.i18n = PROMPTS.i18n = {...thisClass.i18n, ...thisClass.lastJson.translates};
			});
			document.body.addEventListener('namesuggestionloaded', async (event) => {
				if(!(thisClass.lastJson?.names??false)) {return;}
				PROMPTS.names = thisClass.lastJson.names;
			});
			document.body.addEventListener('zipcodeupdated', async (event) => {
				if(thisClass.Swal.isVisible()) {thisClass.Swal.close();}
				document.querySelectorAll('.sos_zip_preview').forEach((el) => {
					if(thisClass.lastJson?.zipcode) {
						el.innerHTML = thisClass.lastJson.zipcode;
					}
				});
			});
			document.body.addEventListener('categorylistsfalied', async (event) => {
				if(!(CAT_PROMPTS?.lastCategoryLink)) {return;}
				location.href = CAT_PROMPTS.lastCategoryLink;
			});
			document.body.addEventListener('categorylistsloaded', async (event) => {
				CAT_PROMPTS.load_template(thisClass);
			});
			document.body.addEventListener('addedToCartSuccess', (event) => {
				Swal.fire({
					icon: "question", width: 600,
					iconHtml: icons.firework,
					title: thisClass.i18n?.thanks_for_order??'Thanks for Order',
					text: thisClass.i18n?.uhvsuccessfullytext??'You\'ve successfully completed your order with Super of the Suburbs.',
					customClass: {popup: 'fwp-confirm_popup'},
					showLoaderOnConfirm: true,
					confirmButtonText: thisClass.i18n?.okay??'Okay',
					allowOutsideClick: () => !Swal.isLoading(),
					didOpen: async () => {},
					preConfirm: async (login) => {return PROMPTS.on_Closed(thisClass);}
				}).then(async (result) => {
					if(result.isConfirmed) {}
				})
			});
			document.body.addEventListener('addedToCartToCheckout', async (event) => {
				if((thisClass.lastJson?.redirectTo) && (PROMPTS?.toSearchQuery)) {
					var href = thisClass.lastJson.redirectTo;
					// href += '?' + PROMPTS.toSearchQuery.map((row) => row.name + '=' + row.value).join('&')
					location.href = href;
				}
			});
		}
		init_i18n() {
			const thisClass = this;
			var formdata = new FormData();
			formdata.append('action', 'sospopsproject/ajax/i18n/js');
			formdata.append('_nonce', thisClass.ajaxNonce);
			thisClass.sendToServer(formdata);

			var formdata = new FormData();
			formdata.append('action', 'sospopsproject/ajax/suggested/names');
			formdata.append('_nonce', thisClass.ajaxNonce);
			thisClass.sendToServer(formdata);
		}
		sendToServer( data ) {
			const thisClass = this;var message;
			$.ajax({
				url: thisClass.ajaxUrl,
				type: "POST",
				data: data,    
				cache: false,
				contentType: false,
				processData: false,
				success: function( json ) {
					thisClass.lastJson = json.data;
					if((json?.data??false)) {
						var message = ((json?.data??false)&&typeof json.data==='string')?json.data:(
							(typeof json.data.message==='string')?json.data.message:false
						);
						if( message ) {
							// thisClass.toast.fire({icon: (json.success)?'success':'error', title: message})
							thisClass.toastify({text: message,className: "info", duration: 3000, stopOnFocus: true, style: {background: (json.success)?'linear-gradient(to right, rgb(255 197 47), rgb(251 229 174))':'linear-gradient(to right, rgb(222 66 75), rgb(249 144 150))'}}).showToast();
						}
						if( json.data.hooks ) {
							json.data.hooks.forEach((hook) => {
								document.body.dispatchEvent(new Event(hook));
							});
						}
					}
				},
				error: function(err) {
					// thisClass.notify.fire({icon: 'warning',title: err.responseText})
					err.responseText = (err.responseText && err.responseText != '')?err.responseText:thisClass.i18n?.somethingwentwrong??'Something went wrong!';
					thisClass.toastify({text: err.responseText,className: "info",style: {background: "linear-gradient(to right, rgb(222 66 75), rgb(249 144 150))"}}).showToast();
					// console.log(err);
				}
			});
		}
		generate_formdata(form=false) {
			const thisClass = this;let data;
			form = (form)?form:document.querySelector('form[name="acfgpt3_popupform"]');
			if (form && typeof form !== 'undefined') {
			  const formData = new FormData(form);
			  const entries = Array.from(formData.entries());
		  
			  data = entries.reduce((result, [key, value]) => {
				const keys = key.split('[').map(k => k.replace(']', ''));
		  
				let nestedObj = result;
				for (let i = 0; i < keys.length - 1; i++) {
				  const nestedKey = keys[i];
				  if (!nestedObj.hasOwnProperty(nestedKey)) {
					nestedObj[nestedKey] = {};
				  }
				  nestedObj = nestedObj[nestedKey];
				}
		  
				const lastKey = keys[keys.length - 1];
				if (lastKey === 'acfgpt3' && typeof nestedObj.acfgpt3 === 'object') {
				  nestedObj.acfgpt3 = {
					...nestedObj.acfgpt3,
					...thisClass.transformObjectKeys(Object.fromEntries(new FormData(value))),
				  };
				} else if (Array.isArray(nestedObj[lastKey])) {
				  nestedObj[lastKey].push(value);
				} else if (nestedObj.hasOwnProperty(lastKey)) {
				  nestedObj[lastKey] = [nestedObj[lastKey], value];
				} else if ( lastKey === '') {
				  if (!Array.isArray(nestedObj[keys[keys.length - 2]])) {
					nestedObj[keys[keys.length - 2]] = [];
				  }
				  nestedObj[keys[keys.length - 2]].push(value);
				} else {
				  nestedObj[lastKey] = value;
				}
		  
				return result;
			  }, {});
		  
			  data = {
				...data?.acfgpt3??data,
			  };
			  thisClass.lastFormData = data;
			} else {
			  thisClass.lastFormData = thisClass.lastFormData?thisClass.lastFormData:{};
			}
			return thisClass.lastFormData;
		}
		transformObjectKeys(obj) {
			const transformedObj = {};
		  
			const addToArray = (fieldKey, key, value) => {
			  const arrayKey = key.split('.')[1];
			  if (!transformedObj[fieldKey][arrayKey]) {
				transformedObj[fieldKey][arrayKey] = [];
			  }
			  transformedObj[fieldKey][arrayKey].push(value);
			}
		  
			for (const key in obj) {
			  if (obj.hasOwnProperty(key)) {
				const value = obj[key];
		  
				if (key.includes('.')) {
				  // Handle keys with dots (arrays)
				  const fieldKey = key.split('.')[0];
				  if (!transformedObj[fieldKey]) {
					transformedObj[fieldKey] = {};
				  }
				  addToArray(fieldKey, key, value);
				} else if (key.includes('[') && key.includes(']')) {
				  // Handle keys with square brackets
				  const matches = key.match(/(.+?)\[(\w+)\]/);
				  if (matches && matches.length >= 3) {
					const fieldKey = matches[1];
					if (!transformedObj[fieldKey]) {
					  transformedObj[fieldKey] = {};
					}
					addToArray(fieldKey, key, value);
				  }
				} else {
				  // Handle regular keys
				  const newKey = key.replace(/\[(\w+)\]/g, '.$1').replace(/^\./, '');
		  
				  if (typeof value === 'object') {
					transformedObj[newKey] = this.transformObjectKeys(value);
				  } else {
					const keys = newKey.split('.');
					let currentObj = transformedObj;
		  
					for (let i = 0; i < keys.length - 1; i++) {
					  const currentKey = keys[i];
					  if (!currentObj[currentKey]) {
						currentObj[currentKey] = {};
					  }
					  currentObj = currentObj[currentKey];
					}
		  
					currentObj[keys[keys.length - 1]] = value;
				  }
				}
			  }
			}
		  
			return transformedObj;
		}
		/**
		 * Asks user for give her location only once.
		 * Asks from a hompage button or listing list screen.
		 */
		init_zip_picker() {
			const thisClass = this;var form, html, config, json, card, node, error;
			document.querySelectorAll(thisClass.classes_zip_picker()).forEach((el)=>{
				el.dataset.handled = true;
				el.addEventListener('click', (event) => {
					event.preventDefault();
					html = PROMPTS.zip_template(thisClass);
					Swal.fire({
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
							const zipCodeResult = document.querySelector('.location-picker .zip-code');
							const findButton = document.querySelector('.location-picker .submit-button');
							const locateMe = document.querySelector('.location-locateme .locate-me');
							locationIcon?.addEventListener("click", (event) => {
								event.preventDefault();
								// Check if geolocation is supported by the browser
								if("geolocation" in navigator) {
									navigator.geolocation.getCurrentPosition(async (position) => {
										try {
											// Get the user's latitude and longitude
											const { latitude, longitude } = position.coords;
											if(thisClass?.responsedZipCode) {
												zipCodeResult.value = thisClass?.responsedZipCode;
												// return;
											}
											const response = await fetch(
												`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latitude}&lon=${longitude}`
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
						preConfirm: async (login) => {return PROMPTS.on_Closed(thisClass);}
					}).then( async (result) => {
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
				});
			});
		}
		classes_zip_picker() {
			var classes = ['#header-menu-zip-pops-launch', '.custom_zip_btn'];
			return classes.map((clas) => clas + ':not([data-handled])').join(', ');
		}
		/**
		 * From service single page, there will be a popup with three steps.
		 * 
		 * Popup steps is following below.
		 * -- Criteria
		 * 		-- Backend setup questions all are in one steps.
		 * -- Contact info
		 * 		-- like - woo multi step checkout
		 * -- Peview
		 */
		init_pops_asks() {
			const thisClass = this;var form, html, config, json, card, node;
			document.querySelectorAll('.custom_pops_btn:not([data-handled])').forEach((el)=>{
				el.dataset.handled = true;
				// thisClass.resizeCartButtons(el);
				// Mode add to cart & action button on a div to fix justify spaces.
				// card = el.parentElement;node = document.createElement('div');
				// node.classList.add('fwp_custom_actions');node.appendChild(el.previousElementSibling);
				// node.appendChild(el);card.appendChild(node);
				
				el.addEventListener('click', (event) => {
					event.preventDefault();
					html = PROMPTS.get_template(thisClass);
					Swal.fire({
						title: false, // thisClass.i18n?.generateaicontent??'Generate AI content',
						width: 700,
						showConfirmButton: false,
						showCancelButton: false,
						showCloseButton: false,
						allowOutsideClick: false,
						allowEscapeKey: true,
						// confirmButtonText: 'Generate',
						// cancelButtonText: 'Close',
						// confirmButtonColor: '#3085d6',
						// cancelButtonColor: '#d33',
						customClass: {popup: 'fwp-swal2-popup'},
						// focusConfirm: true,
						// reverseButtons: true,
						// backdrop: `rgba(0,0,123,0.4) url("https://sweetalert2.github.io/images/nyan-cat.gif") left top no-repeat`,
						// backdrop: `rgb(255 255 255)`,

						showLoaderOnConfirm: true,
						allowOutsideClick: false, // () => !Swal.isLoading(),
						
						html: html,
						// footer: '<a href="">Why do I have this issue?</a>',
						didOpen: async () => {
							config = JSON.parse((el.dataset?.config)?el.dataset.config:'{}');
							json = {product_id: config.id};
							
							var formdata = new FormData();
							formdata.append('action', 'sospopsproject/ajax/search/product');
							formdata.append('zip_code', thisClass.config?.zipCode);
							formdata.append('dataset', await JSON.stringify(json));
							formdata.append('_nonce', thisClass.ajaxNonce);

							thisClass.sendToServer(formdata);
							PROMPTS.init_prompts(thisClass);
						},
						preConfirm: async (login) => {return PROMPTS.on_Closed(thisClass);}
					}).then( async (result) => {
						PROMPTS.freezedSteps = 0;
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
				});
			});
			window.addEventListener("resize", () => {
				document.querySelectorAll('.init_cusomizeaddtocartbtn').forEach((el)=>{
					thisClass.resizeCartButtons(el);
				});
			});
		}
		resizeCartButtons(el) {
			// [el, el.previousElementSibling].forEach((btn)=>{
			// 	// btn.setAttribute('style',((window?.innerWidth??(screen?.width??0)) <= 500)?'width: 48% !important;padding: 10px 10px !important;font-size: 10px !important;display: unset !important;':'padding: 10px 5px !important;font-size: 15px !important;');
			// });
			el.previousElementSibling.classList.remove('button');
		}
		init_pro_regis() {
			const thisClass = this;thisClass.proRegFormContainer = false;
			document.querySelectorAll('.professional_registration_btn').forEach((btn) => {
				btn.addEventListener('click', (event) => {
					event.preventDefault();
					if(! thisClass.proRegFormContainer) {
						const wrap = document.querySelector('.professional_registration_wrap');
						if(! wrap) {return;}
						const container = wrap.querySelector('.uael-cf7-container');
						if(! container) {return;}
						thisClass.proRegFormContainer = document.createElement('div');
						thisClass.proRegFormContainer.appendChild(container);
					}
					
					thisClass.Swal.fire({
						title: false, width: 1000, padding: '1em',
						background: 'rgb(255 255 255)',
						showConfirmButton: false,
						showCancelButton: false,
						showCloseButton: true,
						allowOutsideClick: false,
						allowEscapeKey: true,
						showDenyButton: false,
						// backdrop: `rgb(137 137 137 / 74%)`,
						html: `<div class="pro_reg_cf7"></div>`,
						customClass: {popup: 'fwp-pro_reg'},
						didOpen: async () => {
							document.querySelector('.pro_reg_cf7')?.appendChild(thisClass.proRegFormContainer.childNodes[0]);
						},
						allowOutsideClick: () => !Swal.isLoading(),
					}).then((res) => {
						thisClass.proRegFormContainer.appendChild(document.querySelector('.pro_reg_cf7')?.querySelector('.uael-cf7-container'));
					});
				});
			});
		}
		init_category_pops() {
			CAT_PROMPTS.init(this);
		} 
		
		clearAllFromCart() {
			document.querySelectorAll('.woocommerce-page #content table.cart td.product-remove a').forEach((el)=>{el.click();});
		}
		esc_attr(text) {
			return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		}
		findPosY(obj) {
			var curtop = 0;
			if (typeof (obj.offsetParent) != 'undefined' && obj.offsetParent) {
			  while (obj.offsetParent) {
				curtop += obj.offsetTop;
				obj = obj.offsetParent;
			  }
			  curtop += obj.offsetTop;
			}
			else if (obj.y)
			  curtop += obj.y;
			return curtop;
		}

		init_single_service_sidebar() {
			const thisClass = this;
			var leftSideBar = document.querySelector('#single-service-leftsidebar');
			if(!leftSideBar) {return;}
			var leftSideBarFromTop = thisClass.findPosY(leftSideBar);
			var leftSideBarHeight = leftSideBar.offsetHeight;
			var leftSideBarList = leftSideBar.querySelector('#sidebar_menus .elementor-icon-list-items')
			var leftSideBarListHeight = leftSideBarList.offsetHeight;
			leftSideBarList.style.position = 'relative';
			window.onscroll = function() {
				var currentPosition = window.scrollY;
				if(
					currentPosition > leftSideBarFromTop && 
					currentPosition < (leftSideBarFromTop + (leftSideBarHeight - leftSideBarListHeight - 40))
				) {
					// if((currentPosition - leftSideBarFromTop) >= leftSideBarFromTop) {
						leftSideBarList.style.top = (currentPosition - leftSideBarFromTop) + 'px';
					// }
					
				}
			};
			document.querySelectorAll('#sidebar_menus .elementor-icon-list-items').forEach((ul, ulI) => {
				ul.classList.add('initiated');
				[...ul.children].forEach((li) => {
					li.children[0].addEventListener('click', (event) => {
						[...ul.children].forEach((cli) => {cli.children[0].classList.remove('visiblly-active');});
						li.children[0].classList.add('visiblly-active');
					});
				});
				if(ulI == 0) {
					ul.children[0].children[0].classList.add('visiblly-active');
				}
			});
		}
		
	}
	new FutureWordPress_Frontend();
} )( jQuery );
