
import Swal from "sweetalert2";
import PROMPTS from "./prompts";
import Toastify from 'toastify-js';
import Sortable from 'sortablejs';
import mediaImages from "./media";
import WaveSurfer from 'wavesurfer.js';
import tippy from 'tippy.js';
import Exim from "./exim";
import Post from "./post";

( function ( $ ) {
	class FWPListivoBackendJS {
		constructor() {
			this.ajaxUrl = fwpSiteConfig?.ajaxUrl??'';
			this.ajaxNonce = fwpSiteConfig?.ajax_nonce??'';
			var i18n = fwpSiteConfig?.i18n??{};this.cssImported = false;
			this.config = fwpSiteConfig?.config??{};
			this.config = {...this.config, ...fwpSiteConfig};
			this.WaveSurfer = WaveSurfer;this.tippy = tippy;
			this.i18n = {
				submit:			'Submit',
				...i18n
			}
			this.setup_hooks();
		}
		setup_hooks() {
			const thisClass = this; // Definign This Class as thisClass
			window.thisClass = this; // Globalizing This Class for access.
			this.Post = new Post(this); // Init Server request function.
			this.mediaImages = mediaImages; // Registering Media Scripts.
			this.prompts = PROMPTS; // Registering Prompts Scripts.
			PROMPTS.i18n = this.i18n; // Registering I18n Datasets.
			this.Sortable = Sortable; // Registering Jquery Sortable Library.
			this.Exim = new Exim(this);// Init Export & Import function.
			this.Swal = Swal; // Registewring Sweetalert2 Library.
			this.init_i18n(); // Initializing I18n Functions.
			this.init_toast();
			this.init_events();
			this.init_button();
			this.init_tooltips();
			this.init_wavesurfer();
			this.ask4sosname();
			this.init_custom_services_repeater();
			mediaImages.uploadTexToImage(this);
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
			const thisClass = this;var html;
			document.body.addEventListener('gotproductpopupresult', async (event) => {
				thisClass.prompts.lastJson = thisClass.lastJson;
				html = document.createElement('div');
				html.appendChild(await PROMPTS.get_template(thisClass));
				// && json.header.product_photo
				if(thisClass.Swal.isVisible()) {
					thisClass.Swal.update({
						html: html.innerHTML
					});
					setTimeout(() => {
						if(thisClass.lastJson.product!=''&&thisClass.lastJson.product.length>=1) {
							thisClass.prompts.do_fetch(thisClass);
						}
						thisClass.prompts.init_events(thisClass);
						thisClass.prompts.load_cutocomplete_data(thisClass);
					}, 300);
				}
			});
			document.body.addEventListener('product_updated', async (event) => {
				var button = document.querySelector('.save-this-popup');
				if(button) {button.removeAttribute('disabled');}

				if(thisClass?.isImporting) {
					thisClass.isImporting = false;
					thisClass.Swal.close();
					setTimeout(() => {
						document.querySelector('.fwppopspopup-open')?.click();
					}, 1000);
				}
			});
			document.body.addEventListener('ajaxi18nloaded', async (event) => {
				if(!(thisClass.lastJson?.translates??false)) {return;}
				thisClass.i18n = PROMPTS.i18n = {...thisClass.i18n, ...thisClass.lastJson.translates};
			});
			document.body.addEventListener('fields_names_loaded', async (event) => {
				if(!(thisClass.lastJson?.fields_names??false)) {return;}
				thisClass.prompts.autocomplets.fields_names = (thisClass.lastJson?.fields_names??[]).map((row)=>{
					return {label: row[0], value: row[1]};
				});
				PROMPTS.init_intervalevent(thisClass);
			});
		}
		init_i18n() {
			const thisClass = this;
			var formdata = new FormData();
			formdata.append('action', 'sospopsproject/ajax/i18n/js');
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
							json.data.hooks.forEach(( hook ) => {
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
			for (const key in obj) {
			  if (obj.hasOwnProperty(key)) {
				const value = obj[key];
				if (key.includes('[') && key.includes(']')) {
				  // Handle keys with square brackets
				  const matches = key.match(/(.+?)\[(\w+)\]/);
				  if (matches && matches.length >= 3) {
					const newKey = matches[1];
					const arrayKey = matches[2];
		  
					if (!transformedObj[newKey]) {
					  transformedObj[newKey] = [];
					}
		  
					transformedObj[newKey][arrayKey] = value;
				  } else {
					if(key.substr(-2)=='[]') {
						const newKey = key.substr(0, (key.length-2));
						if(!transformedObj[newKey]) {transformedObj[newKey]=[];}
						transformedObj[newKey].push(value);
					}
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
		init_button() {
			const thisClass = this;
			document.querySelectorAll('.fwppopspopup-open').forEach(element => {
				element.addEventListener('click',(event)=>{
					event.preventDefault();
					thisClass.init_popup();
				});
			});
		}
		init_popup() {
			const thisClass = this;var html;
			PROMPTS.lastfieldID = 0;
			thisClass.prompts.lastJson = false;
			html = document.createElement('div');
			html.appendChild(PROMPTS.get_template(thisClass));
			Swal.fire({
				title: false, // thisClass.i18n?.generateaicontent??'Generate AI content',
				width: 600,
				// padding: '3em',
				// color: '#716add',
				// background: 'url(https://png.pngtree.com/thumb_back/fh260/background/20190221/ourmid/pngtree-ai-artificial-intelligence-technology-concise-image_19646.jpg) rgb(255, 255, 255) center center no-repeat',
				showConfirmButton: false,
				showCancelButton: false,
				showCloseButton: true,
				// confirmButtonText: 'Generate',
				cancelButtonText: 'Close',
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				customClass: {
					popup: 'fwp-swal2-popup'
				},
				// focusConfirm: true,
				// reverseButtons: true,
				// backdrop: `rgba(0,0,123,0.4) url("https://sweetalert2.github.io/images/nyan-cat.gif") left top no-repeat`,
				backdrop: `rgba(0,0,123,0.4)`,

				showLoaderOnConfirm: true,
				allowOutsideClick: false, // () => !Swal.isLoading(),
				
				html: html.innerHTML,
				// footer: '<a href="">Why do I have this issue?</a>',
				didOpen: async () => {
					var formdata = new FormData();
					formdata.append('action', 'sospopsproject/ajax/edit/product');
					formdata.append('product_id', thisClass.config?.product_id??'');
					formdata.append('_nonce', thisClass.ajaxNonce);

					thisClass.sendToServer(formdata);
					thisClass.prompts.currentFieldID = 0;
					thisClass.prompts.init_prompts(thisClass);
				},
				preConfirm: () => confirm('Are you sure?'), // async (login) => {return false;}
				preDeny: () => confirm('Are you sure?'), // async (login) => {return false;}
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
		}
		init_wavesurfer() {
			document.querySelectorAll('.fwp-outfit__player[data-audio]').forEach((el) => {
			  // Web Audio example
		  
			  const audio = new Audio();
			  audio.controls = true;
			  audio.src = el.dataset.audio;
		  
			  // Create a WaveSurfer instance and pass the media element
			  const wavesurfer = WaveSurfer.create({
				container: el,
				media: audio,
				waveColor: 'rgb(200, 0, 200)',
				progressColor: 'rgb(100, 0, 100)',
				interact: false, // Disable user interactions with the waveform
			  });
		  
			  // Optionally, add the audio to the page to see the controls
			  el.parentElement.insertBefore(audio, el);
		  
			  // Play the audio and the WaveSurfer when the audio starts playing
			  audio.addEventListener('play', () => {
				wavesurfer.play();
			  });
		  
			  // Pause the WaveSurfer when the audio is paused
			  audio.addEventListener('pause', () => {
				wavesurfer.pause();
			  });
		  
			  // Create Web Audio context
			  const audioContext = new AudioContext();
		  
			  // Define the equalizer bands
			  const eqBands = [32, 64, 125, 250, 500, 1000, 2000, 4000, 8000, 16000];
		  
			  // Create a biquad filter for each band
			  const filters = eqBands.map((band) => {
				const filter = audioContext.createBiquadFilter();
				filter.type = band <= 32 ? 'lowshelf' : band >= 16000 ? 'highshelf' : 'peaking'; // Fixed 'hishelf' to 'highshelf'
				filter.gain.value = Math.random() * 40 - 20;
				filter.Q.value = 1; // resonance
				filter.frequency.value = band; // the cut-off frequency
				return filter;
			  });
		  
			  // Connect the audio to the equalizer
			//   audio.addEventListener(
			// 	'canplay',
			// 	() => {
			// 	  // Create a MediaElementSourceNode from the audio element
			// 	  const mediaNode = audioContext.createMediaElementSource(audio);
		  
			// 	  // Connect the filters and media node sequentially
			// 	  const equalizer = filters.reduce((prev, curr) => {
			// 		prev.connect(curr);
			// 		return curr;
			// 	  }, mediaNode);
		  
			// 	  // Connect the filters to the audio output
			// 	  equalizer.connect(audioContext.destination);
			// 	},
			// 	{ once: true }
			//   );
		  
			  // Create a vertical slider for each band
			//   const container = document.createElement('p');
			//   filters.forEach((filter) => {
			// 	const slider = document.createElement('input');
			// 	slider.type = 'range';
			// 	slider.orient = 'vertical';
			// 	slider.style.appearance = 'slider-vertical';
			// 	slider.style.width = '8%';
			// 	slider.step = 0.1;
			// 	slider.min = -40;
			// 	slider.max = 40;
			// 	slider.value = filter.gain.value;
			// 	slider.oninput = (e) => (filter.gain.value = e.target.value);
			// 	container.appendChild(slider);
			//   });
			//   el.parentElement.insertBefore(container, el);
			});
		}
		init_tooltips() {
			const thisClass = this;
			document.querySelectorAll('.fwp-outfit__image:not([data-handled-tippy])').forEach((el)=>{
				el.dataset.handledTippy = true;
				tippy(el, {
					allowHTML: true,
					content: `
					<div class="fwp-image__tippy">
						<img src="${el.src}" alt="" class="fwp-image__tippy__image">
						<strong class="fwp-image__tippy__price">${el.dataset.item} (${el.dataset.price})</strong>
						<span class="fwp-image__tippy__title">${el.dataset.product}</span>
					</div>`
				});
			});
			document.querySelectorAll('.fwppopspopup-open:disabled').forEach((el)=>{
				tippy(el.parentElement, {
					content: thisClass.i18n?.globallydefined??'This product is globally defined and until disabling forceful definition, you can\'t customize this popup.'
				});
			});
		}
		ask4sosname() {
			const thisClass = this;
			if(window?.teddyNameRequired) {
				document.querySelector('#actions select[name="wc_order_action"]')?.addEventListener('change', (event) => {
					if(event.target.value == 'send_birth_certificates') {
						window.teddyNameRequired.forEach((item) => {
							thisClass.ask4thisTeddyName(item);
						});
					}
				});
			}
		}
		ask4thisTeddyName(item) {
			const thisClass = this;
			const updateBtn = document.querySelector('#poststuff #woocommerce-order-actions .inside button[type="submit"]');
			if(updateBtn) {updateBtn.classList.add('disabled');updateBtn.disabled = true;}
			const action = 'sospopsproject/ajax/update/orderitem';
			Swal.fire({
				title: item.prod_name,
				text: 'Teddy name',
				input: 'text',
				inputAttributes: {
				  autocapitalize: 'off'
				},
				showCancelButton: true,
				confirmButtonText: 'Confirm Name',
				showLoaderOnConfirm: true,
				preConfirm: (login) => {
				  return fetch(`${thisClass.ajaxUrl}?action=${action}&_nonce=${thisClass.ajaxNonce}&order_id=${item.order_id}&item_id=${item.item_id}&teddyname=${login}`)
					.then(response => {
					  if(!response.ok) {
						throw new Error(response.statusText)
					  }
					  return response.json()
					}).then(json => {
						console.log(json);
						if(json?.success) {}
						if(updateBtn) {updateBtn.classList.remove('disabled');updateBtn.removeAttribute('disabled')}
					}).catch(error => {
					  Swal.showValidationMessage(
						`Request failed: ${error}`
					  )
					})
				},
				allowOutsideClick: () => !Swal.isLoading()
			}).then((result) => {
				// if (result.isConfirmed) {}
			})
		}
		init_custom_services_repeater() {
			const thisClass = this;
			const do_repeater = document.querySelector('[data-name="_sos_custom_services[do_repeater_service]"]');
			if(do_repeater) {
				do_repeater?.addEventListener('click', (event) => {
					event.preventDefault();
					var i = (do_repeater.parentElement.childElementCount - 1);
					var template = document.createElement('div');template.classList.add('single-service-veriation');
					template.innerHTML = `<input id="_sos_custom_services-${i}" type="text" name="_sos_custom_services[]" placeholder="" value="" style="width: 100%;"><label for="_sos_custom_services-${i}"></label>`;
					do_repeater.parentElement.insertBefore(template, do_repeater);
				});
				document.querySelectorAll('[id^="_sos_custom_services-"]').forEach((el) => {
					el.nextElementSibling.querySelector('.description').innerHTML = icons.trash;
					el.nextElementSibling.addEventListener('click', (event) => {
						var message = thisClass.i18n?.rusure??'Are you sure?';
						if(confirm(message)) {el.parentElement.remove();}
					});
				});
			}
		}
	}

	new FWPListivoBackendJS();
} )( jQuery );
