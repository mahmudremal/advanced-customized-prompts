/**
 * Frontend Script.
 * 
 * @package SOSPopsProject
 */

import Swal from "sweetalert2";
// import Awesomplete from "awesomplete";
import PROMPTS from "./prompts";
import Toastify from 'toastify-js';
import voiceRecord from "./voicerecord";
import popupCart from "./popupcart";
import flatpickr from "flatpickr";


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
			window.thisClass = this;
			this.prompts = PROMPTS;
			this.Swal = Swal;
			this.flatpickr = flatpickr;
			popupCart.priceSign = this.config?.currencySign??'$';
			this.popupCart = popupCart;
			this.init_toast();
			this.init_events();
			this.init_search_form();
			voiceRecord.i18n = this.i18n;
			PROMPTS.i18n = this.i18n;
			voiceRecord.init_recorder(this);
			this.voiceRecord = voiceRecord;

			// this.move_lang_switcher();
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
			const thisClass = this;var template, html;
			document.body.addEventListener('gotproductpopupresult', async (event) => {
				thisClass.prompts.lastJson = thisClass.lastJson;
				thisClass.popupCart.basePrice = parseFloat(thisClass.prompts.lastJson.product.price);
				thisClass.popupCart.priceSign = thisClass.prompts.lastJson.product.currency;
				template = await thisClass.prompts.get_template(thisClass);
				html = document.createElement('div');html.appendChild(template);
				// && json.header.product_photo
				if(thisClass.Swal && thisClass.Swal.isVisible()) {
					thisClass.prompts.progressSteps = [...new Set(thisClass.prompts.lastJson.product.custom_fields.map((row, i)=>(row.steptitle=='')?(i+1):row.steptitle))];
					thisClass.Swal.update({
						progressSteps: thisClass.prompts.progressSteps,
						currentProgressStep: 0,
						progressStepsDistance: '10px',

						// showCloseButton: true,
						// allowOutsideClick: true,
						// allowEscapeKey: true,
						html: html.innerHTML
					});
					thisClass.prompts.lastJson = thisClass.lastJson;
					if(thisClass.lastJson.product && thisClass.lastJson.product.toast) {
						thisClass.toastify({
							text: thisClass.lastJson.product.toast.replace(/(<([^>]+)>)/gi, ""),
							duration: 45000,
							close: true,
							gravity: "top", // `top` or `bottom`
							position: "left", // `left`, `center` or `right`
							stopOnFocus: true, // Prevents dismissing of toast on hover
							style: {background: 'linear-gradient(to right, #4b44bc, #8181be)'},
							onClick: function(){} // Callback after click
						}).showToast();
					}
					setTimeout(() => {
						thisClass.prompts.init_events(thisClass);
					}, 300);
				}
			});
			document.body.addEventListener('popup_submitting_done', async (event) => {
				var submit = document.querySelector('.popup_foot .button[data-react="continue"]');
				if(submit) {submit.removeAttribute('disabled');}
				if(thisClass.lastJson.redirectedTo) {location.href = thisClass.lastJson.redirectedTo;}
			});
			document.body.addEventListener('popup_submitting_failed', async (event) => {
				var submit = document.querySelector('.popup_foot .button[data-react="continue"]');
				if(submit) {submit.removeAttribute('disabled');}
			});
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
							json.data.hooks.forEach(( hook ) => {
								document.body.dispatchEvent( new Event( hook ) );
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
		init_search_form() {
			const thisClass = this;var form, html, config, json, card, node;
			document.querySelectorAll('.init_cusomizeaddtocartbtn:not([data-handled])').forEach((el)=>{
				el.dataset.handled = true;

				// Mode add to cart & action button on a div to fix justify spaces.
				card = el.parentElement;node = document.createElement('div');
				node.classList.add('fwp_custom_actions');node.appendChild(el.previousElementSibling);
				node.appendChild(el);card.appendChild(node);
				
				el.addEventListener('click', (event) => {
					event.preventDefault();
					html = PROMPTS.get_template(thisClass);
					Swal.fire({
						title: false, // thisClass.i18n?.generateaicontent??'Generate AI content',
						width: 600,
						// padding: '3em',
						// color: '#716add',
						// background: 'url(https://png.pngtree.com/thumb_back/fh260/background/20190221/ourmid/pngtree-ai-artificial-intelligence-technology-concise-image_19646.jpg) rgb(255, 255, 255) center center no-repeat',
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
						backdrop: `rgb(255 255 255)`,

						showLoaderOnConfirm: true,
						allowOutsideClick: false, // () => !Swal.isLoading(),
						
						html: html,
						// footer: '<a href="">Why do I have this issue?</a>',
						didOpen: async () => {
							config = JSON.parse(el.dataset.config);
							json = {product_id: config.id};
							
							var formdata = new FormData();
							formdata.append('action', 'futurewordpress/project/ajax/search/product');
							formdata.append('dataset', await JSON.stringify(json));
							formdata.append('_nonce', thisClass.ajaxNonce);

							thisClass.sendToServer(formdata);
							thisClass.prompts.init_prompts(thisClass);
						},
						preConfirm: async (login) => {return thisClass.prompts.on_Closed(thisClass);}
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

		move_lang_switcher() {
			var node, wrap, lang, next;
			lang = document.querySelector('#trp-floater-ls.trp-language-switcher-container.trp-bottom-right.trp-color-dark');
			next = document.querySelector('.elementor-element.elementor-search-form--skin-full_screen.elementor-widget__width-auto.elementor-widget.elementor-widget-search-form');
			if(lang && next) {
				node = document.createElement('div');node.classList.add('elementor-element', 'elementor-element-afd7b23', 'elementor-widget__width-auto', 'elementor-view-default', 'elementor-widget', 'elementor-widget-icon');
				wrap = document.createElement('div');wrap.classList.add('elementor-widget-container');
				wrap.appendChild(lang);node.appendChild(wrap);
				next.parentElement.insertBefore(node, next);
			}
		}
	}
	new FutureWordPress_Frontend();
} )( jQuery );
