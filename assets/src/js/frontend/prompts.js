import icons from "./icons";
import { secondStepInputFields } from "./database";

// NiceSelect.bind(document.getElementById("a-select"), {searchable: true, placeholder: 'select', searchtext: 'zoek', selectedtext: 'geselecteerd'});

const PROMPTS = {
    i18n: {},voices: {}, names: [], global_cartBtn: false,
    allStepsIn1Screen: true, freezedSteps: 0,
    zip_template: (thisClass) => {
        const subTitle = PROMPTS.i18n?.findsupersrvcinarea??'Simply Enter Your Location or Zip Code';
        const txtFind = PROMPTS.i18n?.findservices??'Locate Local Services';
        const enterZipCode = PROMPTS.i18n?.enterzipcode??'Locate Me or Enter Your Zip Code';
        const locateMe = PROMPTS.i18n?.locateme??'Locate me';
        const Or = PROMPTS.i18n?.or??'or';
        const html = `
        <p class="swal2-html-container__subtitle">${subTitle}</p>
        <div class="swal2-html-container__form">
            <div class="location-picker">
                <div class="icon-container">
                    <i class="fas fa-map-marker-alt" fa-bullseye></i>
                </div>
                <div class="input-container">
                    <input type="text" class="zip-code" placeholder="${enterZipCode}" value="${thisClass.config?.userzip??''}">
                </div>
                <button class="submit-button">${txtFind}</button>
            </div>
            <div class="location-locateme">
                <p class="location-locateme__text">${Or} <a class="locate-me" href="#">${locateMe}</a></p>
            </div>
        </div>
        `;
        return html;
    },
    get_template: (thisClass) => {
        var json, html;PROMPTS.global_cartBtn = false;
        html = document.createElement('div');html.classList.add('dynamic_popup');
        if(PROMPTS?.allStepsIn1Screen) {html.classList.add('allsteps1screen');}
        if(PROMPTS.lastJson) {
            html = PROMPTS.generate_template(thisClass);
        } else {
            html.classList.add('dynamic_popup__preload');
            html.innerHTML = `<div class="spinner-material"></div><h3>${PROMPTS.i18n?.pls_wait??'Please wait...'}</h3>`;
        }
        return html;
    },
    init_prompts: (thisClass) => {
        PROMPTS.core = thisClass;
    },
    init_events: (thisClass) => {
        document.querySelectorAll('.popup_foot .button[data-react], .back2previous_step[data-react="back"]').forEach((el) => {
            el.addEventListener('click', (event) => {
                event.preventDefault();
                switch (el.dataset.react) {
                    case 'back':
                        PROMPTS.do_pagination(false, thisClass);
                        break;
                    default:
                        PROMPTS.do_pagination(true, thisClass);
                        break;
                }
            });
        });
        document.querySelectorAll('.toggle-password:not([data-handled])').forEach((el) => {
            el.dataset.handled = true;
            el.addEventListener('click', (event) => {
                event.preventDefault();
                var icon = (el.childNodes && el.childNodes[0])?el.childNodes[0]:false;
                if(!icon) {return;}
                switch (icon.classList.contains('fa-eye')) {
                    case false:
                        el.previousSibling.type = 'password';
                        icon.classList.add('fa-eye');
                        icon.classList.remove('fa-eye-slash');
                        break;
                    default:
                        el.previousSibling.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                        break;
                }
            });
        });
        document.querySelectorAll('.form-control[name="field.9000"]:not([data-handled])').forEach((input) => {
            input.dataset.handled = true;
            let awesomplete = new Awesomplete(input, {
                minChars: 3,
                maxItems: 5,
                autoFirst: true,
                // list: suggestions
            });
            input.addEventListener('input', function() {
                const query = input.value;
                let keyword = document.querySelector('#keyword_search');
                keyword = (keyword)?keyword.value:'';
                // Make the AJAX request to fetch suggestions
                fetch(thisClass.ajaxUrl + '?action=sospopsproject/datastore/get_autocomplete&term=location&query='+encodeURIComponent(query)+'&keyword='+encodeURIComponent(keyword))
                  .then(response => response.json())
                  .then(data => {
                    awesomplete.list = (data?.data??data).map((row)=>row?.name??row); // Update the suggestions list
                  })
                  .catch(error => {
                    console.error('Error fetching suggestions:', error);
                  });
            });
        });
        document.querySelectorAll('.popup_close:not([data-handled])').forEach((el) => {
            el.dataset.handled = true;
            el.addEventListener('click', (event) => {
                event.preventDefault();
                if(confirm(PROMPTS.i18n?.rusure2clspopup??'Are you sure you want to close this popup?')) {
                    thisClass.Swal.close();
                }
            });
        });

        document.querySelectorAll('.dynamic_popup').forEach((popup) => {
            var fields = document.querySelector('.tc-extra-product-options.tm-extra-product-options');
            if(!fields) {return;}
            if(!document.querySelector('.tc-extra-product-options-parent')) {
                var node = document.createElement('div');
                node.classList.add('tc-extra-product-options-parent');
                fields.parentElement.insertBefore(node, fields);
            }
            popup.innerHTML = '';popup.appendChild(fields);// jQuery(fields).clone().appendTo(popup);
            
            setTimeout(() => {
                popup.querySelectorAll('[id]').forEach((el) => {el.id = el.id+'_popup';});
                popup.querySelectorAll('label[for]').forEach((el) => {el.setAttribute('for', el.getAttribute('for')+'_popup');});
            }, 200);

            document.querySelectorAll('.dynamic_popup .tm-collapse').forEach((el) => {
                var head = el.firstChild;var body = el.querySelector('.tm-collapse-wrap');
                head.classList.remove('toggle-header-closed');head.classList.add('toggle-header-open');
                head.querySelector('.tcfa.tm-arrow').classList.add('tcfa-angle-up');
                head.querySelector('.tcfa.tm-arrow').classList.remove('tcfa-angle-down');
                body.classList.remove('closed');body.classList.add('open', 'tm-animated', 'fadein');
            });
        });
        document.querySelectorAll('.dynamic_popup input[type="checkbox"], .dynamic_popup input[type="radio"]').forEach((el) => {
            el.addEventListener('change', (event) => {
                if(el.parentElement) {
                    if(el.parentElement.classList.contains('form-control-checkbox__image')) {
                        if(el.checked) {
                            el.parentElement.classList.add('checked_currently');
                        } else {
                            el.parentElement.classList.remove('checked_currently');
                        }
                    } else if(el.parentElement.classList.contains('form-control-radio__image')) {
                        document.querySelectorAll('input[name="'+el.name+'"]').forEach((radio) => {
                            radio.parentElement.classList.remove('checked_currently');
                        });
                        if(el.checked) {
                            el.parentElement.classList.add('checked_currently');
                        }
                    } else {}
                }
            });
        });
        /**
         * .form-fields__group__outfit instead of .popup_body
         */
        document.querySelectorAll('.dynamic_popup .popup_body input[type=checkbox][data-cost], .dynamic_popup .popup_body input[type=radio][data-cost]').forEach((el) => {
            el.addEventListener('change', (event) => {
                var img, frame, title, identity, frameHeight, frameWidth;
                frameHeight = 400;frameWidth = 350;
                frame = document.querySelector('.dynamic_popup .header_image');
                if(!frame) {return;} // I can also give here a toast that frame element not found.
                identity = el.name.replaceAll('.', '')+'-'+el.id;

                var isPayableCheckbox = (((el?.previousElementSibling)?.firstChild)?.dataset)?.outfit;
                if(!isPayableCheckbox) {isPayableCheckbox = ((el?.previousElementSibling)?.firstChild)?.src;}

                if(el.checked && isPayableCheckbox) {
                    img = document.createElement('img');img.src = ((el?.previousElementSibling)?.firstChild)?.src;
                    if((((el?.previousElementSibling)?.firstChild)?.dataset)?.outfit??false) {img.src = ((el?.previousElementSibling)?.firstChild)?.dataset.outfit;}
                    img.height = frameHeight;img.width = frameWidth;img.id = identity;
                    img.alt = ((el?.previousElementSibling)?.firstChild)?.alt;img.dataset.name = el.name;
                    if((el.dataset?.layer??false) && el.dataset.layer != '') {img.style.zIndex = parseInt(el.dataset.layer);}
                    
                    if(el.type == 'radio') {
                        frame.querySelectorAll('img[data-name="'+el.name+'"').forEach((images) => {images.remove();});
                    }
                    if((el.dataset?.preview??'false') == 'true') {frame.appendChild(img);}
                    if(el.dataset?.cost??false) {
                        switch(el.type) {
                            case 'radio':
                                document.querySelectorAll('.dynamic_popup input[type="radio"][name="'+el.name+'"][data-cost]').forEach((radio) => {
                                    if(radio.dataset?.calculated??false) {
                                        thisClass.popupCart.removeAdditionalPrice(radio.value, parseFloat(radio.dataset.cost));// radio.checked = false;
                                        radio.removeAttribute('data-calculated');
                                    }
                                    if(radio.checked) {
                                        if(radio.parentElement.classList.contains('checked_currently')) {
                                            thisClass.popupCart.addAdditionalPrice(radio.value, parseFloat(radio.dataset.cost), false);
                                            radio.dataset.calculated = true;
                                        } else {
                                            thisClass.popupCart.removeAdditionalPrice(radio.value, parseFloat(radio.dataset.cost));
                                            radio.removeAttribute('data-calculated');
                                            frame.querySelectorAll('img[data-name="'+radio.name+'"]').forEach((el)=>el.remove());
                                        }
                                    }
                                });
                                break;
                            case 'checkbox':
                                thisClass.popupCart.addAdditionalPrice(el.value, parseFloat(el.dataset.cost), false);
                                break;
                            default:
                                break;
                        }
                    }
                } else {
                    frame.querySelectorAll('#'+identity.replaceAll('.', '')).forEach((images) => {images.remove();});
                    thisClass.popupCart.removeAdditionalPrice(el.value, parseFloat(el.dataset.cost));
                }
            });
        });
        // document.querySelectorAll('.dynamic_popup .popup_body select').forEach((el) => {
        //     el.addEventListener('change', (event) => {
        //         const selectedOption = event.target.options[event.target.selectedIndex];
        //         Object.values(event.target.options).forEach((option) => {
        //             if((selectedOption.dataset?.cost??'')?.trim() != '') {
        //                 thisClass.popupCart.removeAdditionalPrice(option.innerText + ' - ' + option.value, parseFloat(option.dataset.cost));
        //             }
        //         });
        //         if((selectedOption.dataset?.cost??'')?.trim() != '') {
        //             thisClass.popupCart.addAdditionalPrice(selectedOption.innerText + ' - ' + selectedOption.value, parseFloat(selectedOption.dataset.cost), false);
        //         }
        //     });
        // });
        document.querySelectorAll('.dynamic_popup input[type="date"]').forEach((el) => {
            el.type = 'text';thisClass.flatpickr(el, {enableTime: false, dateFormat: "d M, Y"});
        });
        document.querySelectorAll('.dynamic_popup select').forEach((el) => {
            var theSelect = new thisClass.SlimSelect({
                select: el,
                events: {
                    beforeChange: (newVal, oldVal) => {
                        oldVal.forEach((option) => {
                            var option_name = option.text + ' - ' + option.id;var cost = (option?.dataset)?.cost??(option.data?.cost);
                            thisClass.popupCart.removeAdditionalPrice(option_name, parseFloat(cost));
                        });
                        newVal.forEach((option) => {
                            var option_name = option.text + ' - ' + option.id;var cost = (option?.dataset)?.cost??(option.data?.cost);
                            thisClass.popupCart.addAdditionalPrice(option_name, parseFloat(cost));
                        });
                        return true;
                    },
                    afterChange: (newVal) => {}
                },
                settings: {
                    showSearch: (el.childElementCount >= 10)
                }
            });
            if(el?.options && el.options?.selectedIndex) {
                var selectedOption = el.options[el.options.selectedIndex];
                if(selectedOption && selectedOption.dataset?.cost) {
                    thisClass.popupCart.addAdditionalPrice(selectedOption.innerText + ' - ' + selectedOption?.id??'', parseFloat(selectedOption.dataset.cost));
                }
            }
        });

        document.querySelectorAll('.dynamic_popup button[data-type="done"]:not([data-handled])').forEach((done) => {
            done.dataset.handled = true;
            done.addEventListener('click', (event) => {
                done.parentElement.classList.add('d-none');
                done.parentElement.classList.remove('step_visible');
                done.parentElement.parentElement.classList.remove('visible_card');

                var submitBtn = document.querySelector('.popup_foot .button[data-react=continue]');
                if(submitBtn) {submitBtn.style.display = 'flex';}
                
                var index = done.parentElement?.dataset.step;
                var step = document.querySelector('.swal2-progress-step[data-index="'+index+'"]');
                var span = step?.lastChild;
                if(span) {span.innerHTML = icons.tick + span.textContent;}
                if(step) {step.classList.add('swal2-active-progress-step');}

                document.querySelector('.popup_foot__wrap')?.classList.remove('d-none');
                document.querySelector('.popup_body')?.removeAttribute('data-step-type');
            });
        });
        const fieldInfos = '.popup_step__info .form-fields__group__info > div:nth-child';
        thisClass.config.lastTeddyName = '';
        document.querySelectorAll(fieldInfos+'(-n+2) input').forEach((field) => {
            field.addEventListener('change', (event) => {
                switch(field.type) {
                    case 'text':
                        var input = document.querySelector(fieldInfos+'(2) input');
                        // if(input) {
                        //     if(field.value.trim() == '') {input.checked = true;} else {input.checked = false;}
                        // }
                        break;
                    case 'checkbox':
                        var input = document.querySelector(fieldInfos+'(1) input');
                        if(input) {
                            if(field.checked) {
                                thisClass.config.lastTeddyName = input.value;
                                input.value = PROMPTS.names[Math.floor(Math.random() * PROMPTS.names.length)];
                            } else {
                                input.value = thisClass.config.lastTeddyName;
                            }
                        }
                        break;
                    default:
                        break;
                }
            });
            if(field.type == 'checkbox') {
                field.removeAttribute('name');// field.checked = true;
            }
        });
        document.querySelectorAll('.popup_body .popup_step__info .form-fields__group__info input[type="button"]').forEach((btn) => {
            var nthStep = 1;
            btn.addEventListener('click', (event) => {
                var wrap = ((btn?.parentElement)?.parentElement)?.parentElement;
                if(btn.dataset.actLike == 'next') {
                    if(wrap) {
                        if(nthStep >= 3) {
                            btn.value = thisClass.i18n?.done??'Done';
                        }
                        if(nthStep >= 4) {
                            (((wrap?.parentElement)?.parentElement)?.firstChild)?.click();
                            nthStep = 1;wrap.dataset.visibility = nthStep;
                            btn.value = thisClass.i18n?.continue??'Continue';
                            return;
                        }
                        nthStep++;wrap.dataset.visibility = nthStep;
                    }
                } else if(btn.dataset.actLike == 'skip') {
                    (((wrap?.parentElement)?.parentElement)?.firstChild)?.click();
                } else {}
            });
        });
        // document.querySelector('.calculated-prices .price_amount')?.addEventListener('click', (event) => {
        //     document.querySelector('.popup_foot .button[data-react="continue"]')?.click();
        // });
        
        PROMPTS.currentStep = 1;
        PROMPTS.do_pagination(false, thisClass, 0);
    },
    generate_template: (thisClass) => {
        var json = PROMPTS.lastJson;
        var html = document.createElement('div');
        html.innerHTML = (json.header)?`
            ${(json.header.product_photo)?`
            <div class="dynamic_popup__header">
                <span class="back2previous_step fa fa-arrow-left" type="button" data-react="back">${thisClass.i18n?.back??'Back'}</span>
                <!-- <img class="dynamic_popup__header__image" src="${thisClass.config?.siteLogo??''}" alt="">
                <div class="popup-prices">
                    <button class="calculated-prices">
                        <span>${PROMPTS.i18n?.total??'Total'}</span><div class="price_amount">${(PROMPTS.lastJson.product && PROMPTS.lastJson.product.priceHtml)?PROMPTS.lastJson.product.priceHtml:(thisClass.config?.currencySign??'$')+'0.00'}</div>
                    </button>
                </div> -->
                <div class="popup_close fa fa-times"></div>
            </div>
            <div class="header_image" ${(json.header.product_photo != 'empty')?`style="background-image: url('${json.header.product_photo}');"`:``}></div>`:''}
        `:'';
        var fields = PROMPTS.generate_fields(thisClass);
        // console.log(fields);
        html.appendChild(fields);
        html.classList.add('dynamic_popup');
        html.querySelectorAll('.popup_body').forEach((popBody, i) => {
            popBody.style.display = 'none';
            // if(i == 0){} else {}
        });
        return html;
    },
    generate_fields: (thisClass, secStep = false) => {
        var div, node, step, foot, footwrap, btn, back, prices, fields;
        fields = (secStep)?secStep:PROMPTS.get_data(thisClass);
        if(!fields && (thisClass.config?.buildPath??false)) {
            var img = document.createElement('img');img.src = thisClass.config.buildPath+'/icons/undraw_file_bundle_re_6q1e.svg';img.alt = 'Dataset not found';
            return img;
        }
        div = document.createElement('div');node = document.createElement('form');
        node.action=thisClass.ajaxUrl;node.type='post';node.classList.add('popup_body');
        if(PROMPTS?.allStepsIn1Screen) {node.dataset.id = 'criteria';node.style.display = 'block';}
        fields.forEach((field, i) => {
            step = PROMPTS.do_field(field);i++;
            step.dataset.step = field.fieldID;
            node.appendChild(step);
            PROMPTS.totalSteps=(i+1);
        });
        foot = document.createElement('div');foot.classList.add('popup_foot');
        footwrap = document.createElement('div');footwrap.classList.add('popup_foot__wrap');
        // footwrap.innerHTML = `
        //     <ul class="pagination_list">
        //         ${(thisClass?.progressSteps??[]).map((row, i)=>`
        //         <li class="pagination_list__item" data-order="${i}">
        //             <span class="pagination_list__rounded">${row}</span>
        //         </li>
        //         `).join('')}
        //     </ul>
        // `;

        back = document.createElement('button');back.classList.add('btn', 'btn-default', 'button');
        back.type='button';back.dataset.react = 'back';back.innerHTML=PROMPTS.i18n?.back??'Back';
        // back.style.display = 'none';
        footwrap.appendChild(back);
        
        prices = document.createElement('div');prices.classList.add('calculated-prices');
        prices.innerHTML=`<span>${PROMPTS.i18n?.total??'Total'}</span><div class="price_amount">${(PROMPTS.lastJson.product && PROMPTS.lastJson.product.priceHtml)?PROMPTS.lastJson.product.priceHtml:(thisClass.config?.currencySign??'')+'0.00'}</div>`;
        // document.querySelector('.popup-prices')?.appendChild(prices);
        footwrap.appendChild(prices);
        
        btn = document.createElement('button');btn.classList.add('btn', 'btn-primary', 'button');
        btn.type='button';btn.dataset.react='continue';
        btn.innerHTML=`<span>${PROMPTS.i18n?.continue??'Continue'}</span><div class="spinner-circular-tube"></div>`;
        footwrap.appendChild(btn);
        
        div.appendChild(node);foot.appendChild(footwrap);div.appendChild(foot);
        return div;
    },
    str_replace: (str) => {
        var data = PROMPTS.lastJson,
        searchNeedles = {'product.name': data.product.name};
        Object.keys(searchNeedles).forEach((needle)=> {
            str = str.replaceAll(`{{${needle}}}`, searchNeedles[needle]);
        });
        return str;
    },
    get_data: (thisClass) => {
        var fields = PROMPTS.lastJson.product.custom_fields;
        if(!fields || fields=='') {return false;}
        fields.forEach((row, i) => {row.orderAt = (i+1);});
        return fields;
    },
    secondStep: (thisClass) => {
        const service = secondStepInputFields.find((row) => row.fieldID == 7);
        if(service && service?.options) {
            service.options = Object.values(PROMPTS.lastJson.product.service_variations).map((opt) => {return {label: opt};});
        }
        PROMPTS.lastJson.product.existing_data.forEach((field) => {
            var stepField = secondStepInputFields.find((row) => (field.title).replace('*', '').trim().toLowerCase() == (row.steptitle).replace('*', '').trim().toLowerCase());
            console.log(stepField, field);
            if(stepField && ['text'].includes(stepField?.type)) {
                if(field?.value && field.value != '') {
                    // stepField.value = field.value;
                    stepField.default = field.value;
                }
            }
        });
        
        return secondStepInputFields;
    },
    do_field: (field, child = false) => {
        var fields, form, group, fieldset, input, level, span, option, head, image, others, body, div, info, title, done, imgwrap, i = 0;
        div = document.createElement('div');if(!child) {div.classList.add('popup_step', 'd-none', 'popup_step__'+field.type.replaceAll(' ', '-'));}
        if(PROMPTS?.allStepsIn1Screen) {div.classList.remove('d-none');}
        if(!child) {
            done = document.createElement('button');done.type = 'button';done.dataset.type = 'done';
            done.innerHTML = PROMPTS.i18n?.done??'Done';div.appendChild(done);
        }
        if(field?.classes) {
            try {div.classList.add(...(field.classes.split(' ')));} catch (error) {}
        }
        if((field?.heading??'').trim() != '') {
            head = document.createElement('h2');
            head.innerHTML = PROMPTS.str_replace(field?.heading??'');
            div.appendChild(head);
        }
        
        if((field?.subtitle??'')!='') {
            info = document.createElement('p');
            info.innerHTML=PROMPTS.str_replace(field?.subtitle??'');
            div.appendChild(info);
        }
        
        input = level = false;
        fieldset = document.createElement('fieldset');
        fieldset.classList.add('popup_step__fieldset');
        
        if(field?.options && field.options.length <= 4) {fieldset.classList.add('big_thumb');}
        if((field?.label??'') != '') {
            level = document.createElement('label');
            level.innerHTML = PROMPTS.str_replace(field?.label??'');
            level.setAttribute('for',`field_${field?.fieldID??i}`);
        }
        
        switch (field.type) {
            case 'textarea':
                field.nodeEl = input = document.createElement('textarea');input.classList.add('form-control');
                input.name = 'field.'+field.fieldID;
                input.placeholder = PROMPTS.str_replace(field?.placeholder??'');
                input.id = `field_${field?.fieldID??i}`;input.innerHTML = field?.value??(field?.default??'');
                // if(field?.dataset??false) {input.dataset = field.dataset;}
                input.dataset.fieldId = field.fieldID;
                break;
            case 'input':case 'text':case 'button':case 'number':case 'date':case 'time':case 'local':case 'color':case 'range':
                field.nodeEl = input = document.createElement('input');
                input.classList.add('form-control');
                input.name = 'field.'+field.fieldID;
                input.placeholder = PROMPTS.str_replace(field?.placeholder??'');
                input.id = `field_${field?.fieldID??i}`;
                input.setAttribute('value', field?.value??(field?.default??''));
                input.type = (field.type=='input')?'text':field.type;
                // if(field?.dataset??false) {input.dataset = field.dataset;}
                input.dataset.fieldId = field.fieldID;
                if(level) {fieldset.appendChild(level);}
                if(input) {fieldset.appendChild(input);}
                if(input || level) {div.appendChild(fieldset);}
                break;
            case 'select':
                field.nodeEl = input = document.createElement('select');input.classList.add('form-control');
                input.name = 'field.'+field.fieldID;input.id = `field_${field?.fieldID??i}`;
                if((field?.name??'')?.trim() != '') {input.dataset.name = field?.name;}
                // if(field?.dataset??false) {input.dataset = field.dataset;}
                input.dataset.fieldId = field.fieldID;
                (field?.options??[]).forEach((opt,i)=> {
                    option = document.createElement('option');
                    option.value=opt?.label??'';
                    option.innerHTML=opt?.label??'';
                    option.dataset.index = i;
                    if(opt?.cost) {
                        option.dataset.cost = parseFloat(opt?.cost);
                        option.innerHTML += ` (${parseFloat(opt?.cost).toFixed(2)})`
                    }
                    
                    input.appendChild(option);
                });
                
                if(level) {fieldset.appendChild(level);}
                if(input) {fieldset.appendChild(input);}
                if(input || level) {div.appendChild(fieldset);}
                break;
            case 'doll':case 'radio':case 'checkbox':
                input = document.createElement('div');input.classList.add('form-wrap');
                field.options = (field.options)?field.options:[];
                field.type = (field.type == 'doll')?'radio':field.type;
                if((field?.title??'') != '') {
                    title = document.createElement('h4');title.classList.add('title');
                    title.innerHTML = field?.title??'';fieldset.appendChild(title);
                }
                // field.options = field.options.reverse();
                Object.values(field.options).forEach((opt, optI)=> {
                    if(opt && opt.label) {
                        level = document.createElement('label');level.classList.add('form-control-label', 'form-control-'+field.type);
                        // level.setAttribute('for', `field_${field?.fieldID??i}_${optI}`);
                        if(opt.input) {level.classList.add('form-flexs');}
                        span = document.createElement('span');
                        if(opt.imageUrl) {
                            imgwrap = document.createElement('div');
                            imgwrap.classList.add('form-control-'+field.type+'__imgwrap');
                            image = document.createElement('img');image.src = opt.imageUrl;
                            image.alt = opt.label;// level.appendChild(image);
                            level.classList.add('form-control-'+field.type+'__image');
                            input.classList.add('form-wrap__image');
                            if((opt?.thumbUrl??false) && opt.thumbUrl != '') {
                                image.src = opt.thumbUrl;image.dataset.outfit = opt.imageUrl;
                            }
                            imgwrap.appendChild(image);level.appendChild(imgwrap);
                        }
                        if(!opt.input) {
                            opt.cost = ((opt?.cost) && opt.cost !== NaN)?opt.cost:0;
                            span.innerHTML = `<span title="${thisClass.esc_attr(opt.label)}">${opt.label}</span>`+(
                                (opt?.cost??false)?(
                                ' <strong>'+(thisClass.config?.currencySign??'$')+''+ parseFloat(opt.cost).toFixed(2)+'</strong>'
                               ):''
                           );
                        } else {
                            others = document.createElement('input');others.type='text';
                            others.name='field.'+field.fieldID+'.others';others.placeholder=opt.label;
                            others.dataset.fieldId = field.fieldID;others.dataset.index = optI;
                            span.appendChild(others);
                        }
                        option = document.createElement('input');option.value=opt?.value??opt.label;
                        option.name='field.'+field.fieldID+'.option'+((field.type == 'checkbox')?'.' + optI:'');
                        option.dataset.index = optI;option.dataset.fieldId = field.fieldID;
                        option.id=`field_${field?.fieldID??i}_${optI}`;option.type=field.type;
                        if(field?.layer??false) {option.dataset.layer=field.layer;}
                        if((opt?.cost??'') == '') {opt.cost = '0';}option.dataset.cost=opt.cost;
                        if(child) {option.dataset.preview=child;}
                        level.appendChild(option);level.appendChild(span);input.appendChild(level);
                        fieldset.appendChild(input);div.appendChild(fieldset);
                    }
                });
                break;
            case 'password':
                group = document.createElement('div');group.classList.add('input-group', 'mb-3');
                field.nodeEl = input = document.createElement('input');input.classList.add('form-control');
                input.name = 'field.'+field.fieldID;input.setAttribute('value', field?.value??(field?.default??''));
                input.placeholder = PROMPTS.str_replace(field?.placeholder??'');
                input.id = `field_${field?.fieldID??i}`;input.type = (field.type=='input')?'text':field.type;
                // if(field?.dataset??false) {input.dataset = field.dataset;}
                input.dataset.fieldId = field.fieldID;
                var eye = document.createElement('div');
                eye.classList.add('input-group-append', 'toggle-password');
                eye.innerHTML = '<i class="fa fa-eye"></i>';
                group.appendChild(input);group.appendChild(eye);
                if(level) {fieldset.appendChild(level);}
                if(input) {fieldset.appendChild(group);}
                if(input || level) {div.appendChild(fieldset);}
                break;
            case 'confirm':
                field.nodeEl = input = document.createElement('div');input.classList.add('the-success-icon');
                input.innerHTML = field?.icon??'';
                fieldset.appendChild(input);div.appendChild(fieldset);
                break;
            case 'voice':
                field.nodeEl = input = document.createElement('div');input.classList.add('do_recorder');
                // if(field?.dataset??false) {input.dataset = field.dataset;}
                input.innerHTML = field?.icon??'';input.dataset.cost = field?.cost??0;
                fieldset.appendChild(input);div.appendChild(fieldset);
                break;
            case 'outfit':
                field.nodeEl = fields = document.createElement('div');fields.classList.add('form-fields', 'form-fields__group', 'form-fields__group__'+(field.type).replaceAll(' ', ''));
                (field?.groups??[]).forEach((group, groupI)=> {
                    group.fieldID = (field?.fieldID??0)+'.'+(group?.fieldID??groupI);
                    fields.appendChild(PROMPTS.do_field(group, true));
                });
                fieldset.appendChild(fields);div.appendChild(fieldset);
                break;
            case 'info':
                field.nodeEl = fields = document.createElement('div');fields.dataset.visibility = 1;
                fields.classList.add('form-fields', 'form-fields__group', 'form-fields__group__'+(field.type).replaceAll(' ', ''));
                // field.groups = field.groups.reverse();
                var inputsArgs = {}, inputs = {
                    teddy_name: {
                        type: 'text',
                        label: PROMPTS.i18n?.teddyname??'Teddy name',
                        // placeholder: PROMPTS.i18n?.teddyfullname??'Teddy full Name',
                        dataset: {title: PROMPTS.i18n?.teddyfullname??'Teddy full Name'}
                    },
                    choose_name: {
                        type: 'checkbox',
                        label: PROMPTS.i18n?.chooseaname4me??'Choose a name for me',
                        // placeholder: PROMPTS.i18n?.teddyfullname??'Teddy full Name',
                        dataset: {title: PROMPTS.i18n?.teddyfullname??'Teddy full Name'},
                        options: [{value: 'tochoose', label: 'Choose a name for me'}]
                    },
                    teddy_birth: {
                        type: 'date', // default: new Date().toLocaleDateString('en-US'),
                        label: PROMPTS.i18n?.teddybirth??'Birth date',
                        // placeholder: PROMPTS.i18n?.teddybirth??'Date of teddy\'s birth',
                        dataset: {title: PROMPTS.i18n?.teddybirth??'Birth date'}
                    },
                    teddy_reciever: {
                        type: 'text',
                        label: PROMPTS.i18n?.recieversname??'Reciever\'s Name',
                        // placeholder: PROMPTS.i18n?.recieversname??'Reciever\'s Name',
                        dataset: {title: PROMPTS.i18n?.recieversname??'Reciever\'s Name'}
                    },
                    teddy_sender: {
                        type: 'text',
                        label: PROMPTS.i18n?.sendersname??'Created with love by',
                        // placeholder: PROMPTS.i18n?.sendersname??'Created with love by',
                        dataset: {title: PROMPTS.i18n?.sendersname??'Created with love by'}
                    }
                };
                Object.keys(inputs).forEach((type, typeI) => {
                    inputsArgs = {
                        fieldID: (field?.fieldID??0)+'.'+(type?.fieldID??typeI),
                        ...inputs[type]
                    };
                    if(type == 'choose_name' && Object.keys(inputs)[(typeI-1)] == 'teddy_name') {
                        // field[type] = 'on';
                        inputsArgs.default = inputsArgs.value = false;
                    }
                    // if(field[type] == 'on') {}
                    fields.appendChild(PROMPTS.do_field(inputsArgs, true));
                });
                inputsArgs = {
                    fieldID: (field?.fieldID??0)+'.'+10,
                    type: 'button', value: PROMPTS.i18n?.continue??'Continue',
                };
                var btn_next = PROMPTS.do_field(inputsArgs, true);
                btn_next.querySelector('input').dataset.actLike = 'next';
                fields.appendChild(btn_next);
                inputsArgs = {
                    fieldID: (field?.fieldID??0)+'.'+11,
                    type: 'button', value: PROMPTS.i18n?.skip??'Skip',
                };
                var btn_skip = PROMPTS.do_field(inputsArgs, true);
                btn_skip.querySelector('input').dataset.actLike = 'skip';
                fields.appendChild(btn_skip);

                fieldset.appendChild(fields);div.appendChild(fieldset);
                break;
            default:
                // console.log('Failed implimenting '+field.type);
                input = level = false;
                break;
        }
        i++;
        if((field?.extra_fields??false)) {
            field.extra_fields.forEach((extra) => {
                div.appendChild(PROMPTS.do_field(extra, true));
            });
        }
        return div;
    },
    do_submit: async (thisClass, el) => {
        var data = thisClass.generate_formdata(el);
        var args = thisClass.lastReqs = {
            best_of: 1,frequency_penalty: 0.01,presence_penalty: 0.01,top_p: 1,
            max_tokens: parseInt(data?.max_tokens??700),temperature: 0.7,model: data?.model??"text-davinci-003",
        };
        try {
            args.prompt = thisClass.str_replace(
                Object.keys(data).map((key)=>'{{'+key+'}}'),
                Object.values(data),
                thisClass.popup.thefield?.syntex??''
           );
            PROMPTS.lastJson = await thisClass.openai.createCompletion(args);
            var prompt = thisClass.popup.generate_results(thisClass);
            document.querySelector('#the_generated_result').value = prompt;
            // console.log(prompt);
        } catch (error) {
            thisClass.openai_error(error);
        }
    },
    do_pagination: async (plus, thisClass, staticView = false) => {
        var step, root, header, field, back, data, error, submit;PROMPTS.currentStep = PROMPTS?.currentStep??0;
        if(PROMPTS?.allStepsIn1Screen) {
            const continueBtn = document.querySelector('.popup_foot .button[data-react="continue"]');
            if(plus) {continueBtn.disabled = true;setTimeout(() => {continueBtn.disabled = false;}, 1000);}
            setTimeout(async () => {
                const popupBodys = document.querySelectorAll('.dynamic_popup .popup_body');
                if(plus) {PROMPTS.freezedSteps++;} else {PROMPTS.freezedSteps--;}
                if(staticView !== false && popupBodys[staticView]) {PROMPTS.freezedSteps = staticView;}
                if(popupBodys[PROMPTS.freezedSteps]) {
                    popupBodys.forEach((popBody) => {popBody.style.display = 'none';});
                    if(popupBodys[PROMPTS.freezedSteps].dataset.id == 'preview') {
                        data = [];
                        document.querySelectorAll('.popup_body').forEach((form) => {
                            var formdata = thisClass.generate_formdata(form), changedata = [];
                            Object.keys(formdata).forEach((key) => {
                                var elem = form.querySelector('[name="'+key+'"]');
                                changedata.push({
                                    key: elem.parentElement.previousElementSibling.innerHTML,
                                    type: elem?.nodeName,
                                    val: formdata[key]
                                });
                            });
                            data.push(changedata);
                        });
                        PROMPTS.lastFormsData = data;
                        var formentry = [];
                        PROMPTS.lastFormsData.forEach((group, gI) => {
                            group.forEach((row) => {
                                row.group = gI;
                                // row.group = thisClass.prompts.progressSteps[gI];
                                formentry.push(row)
                            });
                        });
                        var chunkSize = 2;var chunks = [];
                        for (let i = 0; i < formentry.length; i += chunkSize) {
                            var chunk = formentry.slice(i, i + chunkSize);
                            chunks.push(chunk);
                        }
                        PROMPTS.lastFormsData = chunks;

                        popupBodys[PROMPTS.freezedSteps].querySelector('.review-informations')?.remove();
                        popupBodys[PROMPTS.freezedSteps].innerHTML += `
                        <div class="review-informations">
                            <div class="form-groups">
                                <div class="form-row">
                                    ${PROMPTS.lastFormsData.map((group) => `
                                        <div class="form-col">
                                            <div class="form-col-8">
                                                <h3 class="">${group.map((row) => `${row.key.trim().replaceAll('*', '')}`).join(' & ')}</h3>
                                                <a class="" href="#" data-add-change="${group[0].group}">${['select'].includes(group[0].type.toLowerCase())?'Add/Change':'Edit'}</a>
                                            </div>
                                            <div class="form-col-4">
                                                ${group.map((row) => `
                                                    <span class="d-block">${row.val}</span>
                                                `).join('')}
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                            <table class="pricing_table">
                                <tbody>
                                    <tr>
                                        <th>SubTotal</th>
                                        <td class="subtotal">${thisClass.popupCart.getSubTotalHtml(2)}</td>
                                    </tr>
                                    <tr>
                                        <th>Taxes and Fees</th>
                                        <td class="fees">${thisClass.popupCart.getTEXnFeesHtml(2)}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Total Amount</th>
                                        <td class="total">${thisClass.popupCart.getTotalHtml(2)}</td>
                                    </tr>
                                </tfoot>
                            </table>
                            <table style="display: none;">
                                <tr>
                                    <th>Key</th>
                                    <th></th>
                                    <th>Value</th>
                                </tr>
                                ${data.map((row) => {
                                    return row.map((item) => `
                                    <tr>
                                        <td>${item.key.trim()}</td>
                                        <td>:</td>
                                        <td>${item.val.trim()}</td>
                                    </tr>`).join('')
                                }).join('')}
                            </table>
                            ${(['flat_rate'].includes(PROMPTS.lastJson.product?.priceType))?`
                                ${
                                // condition made here to apear instruction insetad of using credit card information.
                                (true)?`
                                <div class="payment_instruction">
                                    <h3>${PROMPTS.i18n?.paymentinstructions??'Payment Instructions'}</h3>
                                    <p>${PROMPTS.i18n?.paymentinstructions_subtitle??'Follow these steps to complete your purchase:'}</p>
                                    <ol>
                                        <li>${PROMPTS.i18n?.payinstruct_1??'Click the "Buy Now" button below.'}</li>
                                        <li>${PROMPTS.i18n?.payinstruct_2??'You will be redirected to the Stripe payment screen.'}</li>
                                        <li>${PROMPTS.i18n?.payinstruct_3??'Complete your payment on the Stripe secure checkout page.'}</li>
                                        <li>${PROMPTS.i18n?.payinstruct_4??'Once your payment is successful, your form will be approved.'}</li>
                                    </ol>
                                </div>
                                `:`
                                <div class="payment_table">
                                    <h3>${PROMPTS.i18n?.chooseurservice??'Choose Your Card'}</h3>
                                    <p>${PROMPTS.i18n?.entrurcardinforest??'Enter your card information below. You will be charged after service has been rendered.'}</p>
                                    <div class="form-row">
                                        <div class="form-col form-col__card">
                                            <input name="" type="text" placeholder="${PROMPTS.i18n?.cardnumber??'Card Number'}" pattern="[0-9]"/>
                                        </div>
                                        <div class="form-col form-col__expiration">
                                            <input name="" type="text" placeholder="${PROMPTS.i18n?.mm_yyyy??'MM/YYYY'}" pattern="[0-9]"/>
                                        </div>
                                        <div class="form-col form-col__cvc">
                                            <input name="" type="text" placeholder="${PROMPTS.i18n?.cvc??'CVC'}" pattern="[0-9]"/>
                                        </div>
                                    </div>
                                    <div class="form-row form-row__foot">
                                        <div class="form-col form-col__card">
                                            <!-- <img src="payments methods" alt="" /> -->
                                            ${icons.cards}
                                        </div>
                                        <div class="form-col form-col__cvc">
                                            ${icons.lock}
                                            <p class="text-dark">${PROMPTS.i18n?.paymentsecuritymsg??'Safe and secure 256-BITSSL encrypted payment.'}</p>
                                        </div>
                                    </div>
                                </div>
                                `
                                }
                            `:``}
                            
                        </div>
                        `;
                        
                        PROMPTS.change_submit_btn_text(
                            ['flat_rate'].includes(PROMPTS.lastJson.product?.priceType)?'booknow':'get_quotation'
                        );
                        
                        setTimeout(() => {
                            const checkoutForm = document.querySelector('#checkout-form-container');
                            if(checkoutForm) {
                                checkoutForm.style.display = 'block';
                                popupBodys[PROMPTS.freezedSteps].appendChild(checkoutForm);
                            }
                            window.popupBodys = popupBodys[PROMPTS.freezedSteps];

                            setTimeout(() => {
                                document.querySelectorAll('[data-add-change]').forEach((link) => {
                                    link.addEventListener('click', (event) => {
                                        event.preventDefault();
                                        // PROMPTS.progressStepsIed = PROMPTS.progressSteps.map((step, i) => {return {i: i, text: step};});
                                        // var toStep = PROMPTS.progressStepsIed.find((step) => step.text.toLowerCase() == link.dataset.addChange.toLowerCase());
                                        if(link.dataset?.addChange) {
                                            // PROMPTS.currentStep = (toStep.i - 0);
                                            PROMPTS.do_pagination(false, thisClass, 
                                                parseInt(link.dataset.addChange)
                                                // ((toStep.i - 1) < 0)?0:(toStep.i - 1)
                                            );
                                        }
                                    });
                                });
                            }, 500);
                        }, 500);
                    } else {
                        PROMPTS.change_submit_btn_text('continue');
                    }

                    popupBodys[PROMPTS.freezedSteps].style.display = 'flex';
                    setTimeout(() => {
                        var popup = document.querySelector('.dynamic_popup');
                        thisClass.frozenNode = document.createElement('div');
                        thisClass.frozenNode.appendChild(popup);
                        setTimeout(() => {
                            thisClass.Swal.update({currentProgressStep: PROMPTS.freezedSteps});
                            setTimeout(() => {
                                var popContainer = document.querySelector('.swal2-html-container');
                                popContainer.innerHTML = '';
                                popContainer.appendChild(
                                    thisClass.frozenNode.querySelector('.dynamic_popup')
                                );
                            }, 100);
                        }, 100);
                    }, 300);
                    
                } else {
                    if(plus) {PROMPTS.freezedSteps--;} else {PROMPTS.freezedSteps++;}
                    error = thisClass.i18n?.nxtstepundrdev??'Next step under develpment.';
                    thisClass.toastify({text: error, style: {background: "linear-gradient(to right, rgb(222 66 75), rgb(249 144 150))"}}).showToast();
                    if(plus) {
                        const changedata = [];
                        document.querySelectorAll('.popup_body').forEach((form) => {
                            var formdata = thisClass.generate_formdata(form), input;
                            Object.keys(formdata).forEach((key) => {
                                input = form.querySelector('[name="'+key+'"]')
                                changedata.push({
                                    key: key, name: input.dataset?.name,
                                    title: (input.parentElement?.previousElementSibling)?.innerHTML,
                                    value: formdata[key]
                                });
                            });
                        });
                        const filteredData = changedata.filter(item => item?.name !== undefined && item.name !== null);
                        PROMPTS.lastFormsData = changedata;PROMPTS.toSearchQuery = filteredData;

                        var formdata = new FormData();
                        formdata.append('action', 'sospopsproject/ajax/cart/add');
                        formdata.append('_nonce', thisClass.ajaxNonce);
                        // const generated = await PROMPTS.get_formdata(thisClass, formdata);
                        formdata.append('charges', JSON.stringify(thisClass.popupCart.additionalPrices));
                        formdata.append('dataset', JSON.stringify(changedata));
                        formdata.append('product_id', PROMPTS.lastJson.product.id);
                        formdata.append('product_type', 
                            ['flat_rate'].includes(PROMPTS.lastJson.product?.priceType)?'add':'get_quotation'
                        );
                        formdata.append('calculated', thisClass.popupCart.getTotal());
                        formdata.append('quantity', 1);
                        thisClass.sendToServer(formdata);
                    }
                }
            }, 1000);
        } else {
            root = '.fwp-swal2-popup .popup_body .popup_step';
            if(!PROMPTS.lastJson.product || !PROMPTS.lastJson.product.custom_fields || PROMPTS.lastJson.product.custom_fields=='') {return;}
            if(PROMPTS?.global_cartBtn || await PROMPTS.beforeSwitch(thisClass, plus)) {
                PROMPTS.currentStep = (plus)?(
                    (PROMPTS.currentStep < PROMPTS.totalSteps)?(PROMPTS.currentStep+1):PROMPTS.currentStep
            ):(
                    (PROMPTS.currentStep > 0)?(PROMPTS.currentStep-1):PROMPTS.currentStep
            );
                if(PROMPTS.currentStep <= 0) {return;}
                submit = document.querySelector('.popup_foot .button[data-react="continue"]');
                if(submit && submit.classList) {
                    if(PROMPTS.currentStep >= (PROMPTS.totalSteps-1) || PROMPTS?.global_cartBtn) {
                        submit.firstElementChild.innerHTML = PROMPTS.i18n?.done??'Done';
                    } else {
                        submit.firstElementChild.innerHTML = PROMPTS.i18n?.continue??'Continue';
                    }
                }
                
                field = PROMPTS.lastJson.product.custom_fields.find((row)=>row.orderAt==PROMPTS.currentStep);
                if(plus && field && field.type == 'confirm' && ! await PROMPTS.do_search(field, thisClass)) {
                    return false;
                }

                if(PROMPTS.currentStep >= PROMPTS.totalSteps || PROMPTS?.global_cartBtn) {
                    step = document.querySelector('.popup_step.step_visible');data = [];
                    data = thisClass.transformObjectKeys(thisClass.generate_formdata(document.querySelector('.popup_body')));
                    
                    console.log('Submitting...');
                    submit = document.querySelector('.popup_foot .button[data-react="continue"]');
                    if(submit && submit.classList) {
                        submit.setAttribute('disabled', true);
                        PROMPTS.currentStep--;

                        // data.product = PROMPTS.lastJson.product.id;
                        var formdata = new FormData();
                        formdata.append('action', 'sospopsproject/ajax/cart/add');
                        formdata.append('_nonce', thisClass.ajaxNonce);
                        const generated = await PROMPTS.get_formdata(thisClass, formdata);
                        
                        formdata.append('charges', JSON.stringify(thisClass.popupCart.additionalPrices));
                        formdata.append('dataset', JSON.stringify(generated));
                        formdata.append('product_id', PROMPTS.lastJson.product.id);
                        formdata.append('product_type', 
                            ['flat_rate'].includes(PROMPTS.lastJson.product?.priceType)?'add':'get_quotation'
                        );
                        formdata.append('calculated', thisClass.popupCart.getTotal());
                        formdata.append('quantity', 1);
                        thisClass.sendToServer(formdata);
                        PROMPTS.global_cartBtn = false;

                        setTimeout(() => {submit.removeAttribute('disabled');}, 100000);
                    }
                    // if(PROMPTS.validateField(step, data, thisClass)) {
                    // } else {console.log('Didn\'t Submit');}
                } else {
                    document.querySelectorAll('.popup_foot .button[data-react="back"], .back2previous_step[data-react="back"]').forEach((back) => {
                        if(!plus && PROMPTS.currentStep<=1) {back.classList.add('invisible');}
                        else {back.classList.remove('invisible');}
                    });
                    
                    field = PROMPTS.lastJson.product.custom_fields.find((row)=>row.orderAt==PROMPTS.currentStep);
                    header = document.querySelector('.header_image');
                    if(header) {
                        if(field && field.headerbgurl!='') {
                            jQuery(header).css('background-image', 'url('+field.headerbgurl+')');
                            // header.innerHTML = '';
                        }
                    }
                    document.querySelectorAll(root+'.step_visible').forEach((el) => {el.classList.add('d-none');el.classList.remove('step_visible');});
                    step = document.querySelector(root+'[data-step="'+(field?.fieldID??PROMPTS.currentStep)+'"]');
                    if(step) {
                        if(!plus) {step.classList.add('popup2left');}
                        step.classList.remove('d-none');setTimeout(() => {step.classList.add('step_visible');},300);
                        if(!plus) {setTimeout(() => {step.classList.remove('popup2left');},1500);}
                    }

                    // Change swal step current one.
                    var popup = document.querySelector('.dynamic_popup');
                    var popupParent = (popup)?popup.parentElement:document.querySelector('.swal2-html-container');
                    thisClass.frozenNode = document.createElement('div');
                    thisClass.frozenNode.appendChild(popup);

                    var find = PROMPTS.lastJson.product.custom_fields.find((row)=>row.orderAt == PROMPTS.currentStep);
                    var found = PROMPTS.progressSteps.indexOf(find?.steptitle??false);
                    // thisClass.Swal.update({
                    //     currentProgressStep: ((found)?found:(PROMPTS.currentStep-1)),
                    //     // progressStepsDistance: (PROMPTS.progressSteps.length<=5)?'2rem':(
                    //     //     (PROMPTS.progressSteps.length>=8)?'0rem':'1rem'
                    //     //)
                    // });
                    thisClass.Swal.update({currentProgressStep: (PROMPTS.currentStep-1)});

                    if(popupParent) {popupParent.innerHTML = '';popupParent.appendChild(thisClass.frozenNode.childNodes[0]);}
                    setTimeout(() => {PROMPTS.work_with_pagination(thisClass);}, 300);
                }
            } else {
                console.log('Proceed failed');
            }
        }
    },
    beforeSwitch: async (thisClass, plus) => {
        var field, back, next, elem, last;last = elem = false;
        if(plus) {
            field = PROMPTS.lastJson.product.custom_fields.find((row)=>row.orderAt==PROMPTS.currentStep);
            elem = document.querySelector('.popup_body .popup_step[data-step="'+(field?.fieldID??PROMPTS.currentStep)+'"]');
            elem = (elem && elem.nextElementSibling)?parseInt(elem.nextElementSibling.dataset?.step??0):0;
            // if(!elem || typeof elem.nextElementSibling === 'undefined') {return false;}
            if(elem>=1 && (PROMPTS.currentStep+1) < elem) {
                last = PROMPTS.currentStep;
                PROMPTS.currentStep = (elem-1);
            }
        }
        if(plus && PROMPTS.totalSteps!=0 && PROMPTS.totalSteps<=PROMPTS.currentStep) {
            // Submitting popup!
            if(elem) {PROMPTS.currentStep = last;}
            return (PROMPTS.totalSteps != PROMPTS.currentStep);
        }
        if(plus) {
            var data = thisClass.generate_formdata(document.querySelector('.popup_body'));
            var step = document.querySelector('.popup_step.step_visible'), prev = [];
            if(!step) {return (PROMPTS.currentStep<=0);}
            if(!PROMPTS.validateField(step, data, thisClass)) {return false;}

            step.querySelectorAll('input, select').forEach((el,ei) => {
                // el is the element input
                if(!prev.includes(el.name) && data[el.name] && data[el.name]==el.value) {
                    // item is the fieldset
                    var item = PROMPTS.lastJson.product.custom_fields.find((row, i)=>row.fieldID==el.dataset.fieldId);
                    if(item) {
                        // opt is the options
                        var opt = (item?.options??[]).find((opt,i)=>i==el.dataset.index);
                        // console.log(item, opt);
                        if(!opt) {
                            var group = (item?.groups??[]).find((grp,i)=>grp.fieldID==el.dataset.fieldId);
                            // console.log(group, item.groups);
                            if(group) {
                                opt = (group?.options??[]).find((opt,i)=>i==el.dataset.index);
                                // console.log(opt);
                            }
                        }
                        if(opt) {
                            prev.push(el.dataset.index);
                            if(!item.is_conditional && opt.next && opt.next!='') {
                                next = PROMPTS.lastJson.product.custom_fields.find((row)=>row.fieldID==parseInt(opt.next));
                                // console.log(next);
                                if(next) {
                                    next.returnStep = item.orderAt;
                                    PROMPTS.currentStep = ((next?.orderAt??(next?.fieldID??0))-1);
                                    return true;
                                }
                                return false;
                            } else {
                                // return false;
                            }
                        }
                    }
                }
                return true;
            });
        }
        if(!plus) {
            var current = PROMPTS.lastJson.product.custom_fields.find((row)=>row.orderAt==PROMPTS.currentStep);
            var returnStep = current?.returnStep??false;
            var next = PROMPTS.lastJson.product.custom_fields.find((row)=>row.orderAt==returnStep);
            if(returnStep && next) {
                PROMPTS.currentStep = (parseInt(returnStep)+1);
                current.returnStep=false;
                return true;
            }
        }
        
        return true;
        // return (!plus || PROMPTS.currentStep < PROMPTS.totalSteps);
        // setTimeout(() => {return true;},100);
    },
    validateField: (step, data, thisClass) => {
        // data = thisClass.generate_formdata(document.querySelector('.popup_body'));
        var fieldValue, field;fieldValue = step.querySelector('input, select');
        fieldValue = (fieldValue)?fieldValue?.name??false:false;
        field = PROMPTS.lastJson.product.custom_fields.find((row)=>row.fieldID==step.dataset.step);
        if(!field) {return false;}

        thisClass.Swal.resetValidationMessage();
        switch (field?.type??false) {
            case 'text':case 'number':case 'color':case 'date':case 'time':case 'local':case 'range':case 'checkbox':case 'radio':
                if(field.required && (!data[fieldValue] || data[fieldValue]=='')) {
                    thisClass.Swal.showValidationMessage('You can\'t leave it blank.');
                    return false;
                }
                break;
            default:
                return true;
                break;
        }
        return true;
    },
    do_search: async (field, thisClass) => {
        var submit = document.querySelector('.popup_foot .button[data-react="continue"]');
        if(submit) {submit.disabled = true;}
        var args, request, formdata;
        args = thisClass.transformObjectKeys(thisClass.generate_formdata(document.querySelector('.popup_body')));
        formdata = new FormData();
        // for (const key in args) {
        //     formdata.append(key, args[key]);
        // }
        args.field.product = PROMPTS.lastJson.product.name;
        formdata.append('formdata', JSON.stringify(args));
        formdata.append('_nonce', thisClass.ajaxNonce);
        formdata.append('action', 'sospopsproject/ajax/search/popup');
    
        request = await fetch(thisClass.ajaxUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json'
            },
            body: formdata
        })
        .then(response => response.json())
        .then(data => console.log(data))
        .catch(err => console.error(err));
        
        if(submit) {submit.removeAttribute('disabled');}
        return true;
    },
    on_Closed: (thisClass) => {
        var popup = document.querySelector('.dynamic_popup .tc-extra-product-options.tm-extra-product-options');
        var parent = document.querySelector('.tc-extra-product-options-parent');
        if(parent && popup) {parent.innerHTML = '';parent.appendChild(popup);}
        return true;
    },
    get_formdata: async (thisClass, formdata = false) => {
        var form = thisClass.generate_formdata(document.querySelector('.popup_body'));
        Object.keys(form).forEach((name) => {
            var elem = document.querySelector('[name="'+name+'"]');
            if(elem.value.trim().toLocaleLowerCase() == form[name].trim().toLocaleLowerCase()) {
                var split = name.split('.');split[1] = parseInt(split[1]);
                var field = PROMPTS.lastJson.product.custom_fields.find((row)=>row.fieldID==split[1]);
                var img = elem.previousElementSibling;
                var match = (field?.options??[]).find((row)=>row.label && row.label.toLocaleLowerCase()==form[name].toLocaleLowerCase());
                form[name] = {
                    title: field?.title??(field?.steptitle??(field?.subtitle??'')),
                    name: elem.name,
                    value: form[name],
                    price: match?.cost??(elem.dataset?.cost??0),
                    image: match?.imageUrl??(img?.src??((img?.dataset??{})?.outfit??'')),
                    // field: match
                };
            } else {
                var split = name.split('.');split[1] = parseInt(split[1]);
                var field = PROMPTS.lastJson.product.custom_fields.find((row)=>row.fieldID==split[1]);
                var img = elem.previousElementSibling;var split = name.split('.');split[1] = parseInt(split[1]);
                switch(field.type) {
                    case 'outfit':
                        split[2] = parseInt(split[2]);
                        var match = ((field?.groups??[])[split[2]]?.options??[]).find((row)=>row.label.trim().toLocaleLowerCase()==form[name].trim().toLocaleLowerCase());
                        
                        form[name] = {
                            title: field?.title??(field?.steptitle??(field?.subtitle??'')),
                            name: elem.name,
                            value: form[name],
                            price: match?.cost??(elem.dataset?.cost??0),
                            image: match?.imageUrl??(img?.src??(img.dataset?.outfit??'')),
                            // field: match
                        };
                        break;
                    case 'voice':
                        var match = (field?.options??[]).find((row)=>(row?.label??'').trim().toLocaleLowerCase()==form[name].trim().toLocaleLowerCase());
                        form[name] = {
                            title: field?.title??(field?.steptitle??(field?.subtitle??'')),
                            name: elem.name,
                            value: form[name],
                            price: match?.cost??(elem.dataset?.cost??0),
                            image: match?.imageUrl??(img?.src??(img.dataset?.outfit??'')),
                            // field: match
                        };
                        console.log('voice', match);
                        break;
                    default:
                        var match = (field?.options??[]).find((row)=>(row?.label??'').trim().toLocaleLowerCase()==form[name].trim().toLocaleLowerCase());
                        form[name] = {
                            title: field?.title??(field?.steptitle??(field?.subtitle??'')),
                            name: elem.name,
                            value: form[name],
                            price: match?.cost??(elem.dataset?.cost??0),
                            image: match?.thumbUrl??(match?.imageUrl??(img?.src??(img.dataset?.outfit??''))),
                            // field: match
                        };
                        break;
                }
            }
        });
        const hasVoice = PROMPTS.lastJson.product.custom_fields.find((row)=>(row.type=='voice'));
        if(hasVoice) {
            // if((thisClass.voiceRecord.audioPreview?.src??'') != '') {
            if(thisClass.voiceRecord.recordedBlob !== null) {
                const voiceName = await thisClass.voiceRecord.recordedFileName();
                // PROMPTS.voices[voiceName] = await fetch(thisClass.voiceRecord.audioPreview.src).then(r => r.blob());
                PROMPTS.voices[voiceName] = thisClass.voiceRecord.recordedBlob;
                if(formdata) {
                    formdata.append('voice', PROMPTS.voices[voiceName], voiceName);
                }
                form['field.'+(hasVoice?.orderAt??115)+'.'+(hasVoice?.fieldID??'115')] = {
                    title: PROMPTS.i18n?.voice??'Voice',
                    name: '',
                    value: voiceName,
                    // hasVoice?.steptitle??(hasVoice?.heading??'Voice'),
                    image: '',
                    cost: parseFloat(thisClass.voiceRecord.recordButton.dataset?.cost??'0'),
                    voice: voiceName
                };
            }
        }
        form = thisClass.transformObjectKeys(form);
        // PROMPTS.lastJson.product.custom_fields.map((row)=>(row.type=='voice')?row:false);

        return form;
    },




    generate_contact_step: (thisClass) => {
    },
    work_with_pagination: (thisClass) => {
        var steps = document.querySelector('.swal2-progress-steps');
        var pagin = document.querySelector('.pagination_list');
        // if(pagin) {pagin.parentElement.insertBefore(steps, pagin);pagin.remove();}
        if(pagin) {pagin.innerHTML = steps.innerHTML;pagin.classList.add('swal2-progress-steps');}

        var submit = document.querySelector('.popup_foot .button[data-react="continue"]');
        if(submit) {
            // submit.firstElementChild.innerHTML = PROMPTS.i18n?.continue??'Continue';
        }

        setTimeout(() => {
            document.querySelectorAll('.dynamic_popup .popup_foot__wrap .swal2-progress-steps .swal2-progress-step').forEach((step, index) => {
                step.dataset.index = (index + 1);
                step.addEventListener('click', (event) => {
                    event.preventDefault();
                    document.querySelectorAll('.dynamic_popup .popup_body .popup_step').forEach((el, elI) => {
                        el.classList.remove('step_visible');el.classList.add('d-none');
                        if(step.dataset.index == el.dataset.step) {
                            el.classList.add('step_visible');el.classList.remove('d-none');
                            document.querySelector('.popup_body')?.classList.add('visible_card');
                            if(el.dataset?.step) {
                                var presentStep = PROMPTS.lastJson.product.custom_fields.find((row)=>row.orderAt == el.dataset.step);
                                if(presentStep) {
                                    document.querySelector('.popup_body').dataset.stepType = presentStep.type;
                                }
                            }

                            PROMPTS.global_cartBtn = false;

                            PROMPTS.currentStep = el.dataset.step;
                            var field = PROMPTS.lastJson.product.custom_fields.find((row)=>row.orderAt==PROMPTS.currentStep);
                            var header = document.querySelector('.header_image');
                            if(header) {
                                if(field && field.headerbgurl != '') {
                                    jQuery(header).css('background-image', 'url('+field.headerbgurl+')');
                                    // header.innerHTML = '';
                                }
                            }
                            document.querySelector('.popup_foot__wrap')?.classList.add('d-none');
                        }
                    });
                })
            });
        }, 300);
    },
    on_gotproductpopupresult: async (thisClass) => {
        var template, html;PROMPTS.filtering_dataset(thisClass);
        thisClass.popupCart.additionalPrices = [];
        // if(PROMPTS.lastJson.product?.priceType == 'flat_rate') {}
        thisClass.popupCart.priceSign = PROMPTS.lastJson.product.currency;
        thisClass.popupCart.setBasePrice(PROMPTS.lastJson.product.price);
        
        template = await PROMPTS.get_template(thisClass);
        html = document.createElement('div');html.appendChild(template);
        
        var step1 = document.createElement('div');step1.classList.add('popup_step', 'popup_step__heading');step1.dataset.step = 0;
        step1.innerHTML = `<h2>${thisClass.i18n?.criteriainfo??'Criteria Information'}</h2><p class="text-muted">${thisClass.i18n?.criteriainfosubtitle??'We are available 24/7 to answer your questions?'}</p>`;
        var firstStep = template.querySelector('.popup_body > .popup_step:first-child');
        if(firstStep) {firstStep.parentElement.insertBefore(step1, firstStep);}
        
        // && json.header.product_photo
        if(thisClass.Swal && thisClass.Swal.isVisible()) {
            // PROMPTS.progressSteps = [...new Set(PROMPTS.lastJson.product.custom_fields.map((row, i)=>(row.steptitle=='')?(i+1):row.steptitle))];
            if(PROMPTS?.allStepsIn1Screen) {
                // PROMPTS.freezedSteps = document.createElement('div');
                PROMPTS.progressSteps = [
                    thisClass.i18n?.criteria??'Criteria',
                    thisClass.i18n?.contact??'Contact',
                    thisClass.i18n?.preview??'Preview'
                ];
                
                /** Contact Step */
                var step2 = document.createElement('form'), fields = PROMPTS.secondStep(thisClass), step, foot;
                step2.action=thisClass.ajaxUrl;step2.type='post';step2.classList.add('popup_body', 'popup_body__row');step2.dataset.id = 'contact';

                /** Heading fields */
                var heading = document.createElement('div');heading.classList.add('popup_step', 'popup_step__heading');heading.dataset.step = 0;
                heading.innerHTML = `<h2>${thisClass.i18n?.criteriainfo??'Contact Information'}</h2><p class="text-muted">${thisClass.i18n?.criteriainfosubtitle??'We are available 24/7 to answer your questions?'}</p>`;
                step2.appendChild(heading);

                /** Fields field */
                fields.forEach((field, i) => {
                    step = PROMPTS.do_field(field);i++;
                    step.dataset.step = field.fieldID;
                    step2.appendChild(step);
                    PROMPTS.totalSteps=(i+1);
                });
                
                /** Preview Step */
                // foot.parentElement?.insertBefore(node, foot);
                var step3 = document.createElement('form');step3.action = thisClass.ajaxUrl;
                step3.type='post';step3.classList.add('popup_body', 'popup_body__row');step3.dataset.id = 'preview';
                var heading = document.createElement('div');heading.classList.add('popup_step', 'popup_step__heading');heading.dataset.step = 0;
                heading.innerHTML = `<h2>${thisClass.i18n?.criteriainfo??'Review Information'}</h2><p class="text-muted">${thisClass.i18n?.criteriainfosubtitle??'We are available 24/7 to answer your questions?'}</p>`;
                step3.appendChild(heading);

                var foot = template?.querySelector('.popup_foot');
                if(foot) {
                    foot.parentElement.insertBefore(step2, foot);
                    foot.parentElement.insertBefore(step3, foot);
                    // setTimeout(() => {}, 200);
                }
            } else {
                PROMPTS.progressSteps = [...new Set(
                    PROMPTS.lastJson.product.custom_fields.map((row, i)=>(row.steptitle=='')?(i+1):(
                        `${(row?.stepicon)?`<div class="swal2-progress-step__icon">${row.stepicon}</div>`:``}
                        <span>${row.steptitle}</span>`
                    ))
                )];
            }
            thisClass.Swal.update({
                progressSteps: PROMPTS.progressSteps,
                currentProgressStep: 0,
                html: html.innerHTML
            });
            PROMPTS.lastJson = thisClass.lastJson;
            if(thisClass.lastJson?.product && thisClass.lastJson.product?.toast) {
                thisClass.toastify({
                    text: thisClass.lastJson.product.toast.replace(/(<([^>]+)>)/gi, ""),
                    duration: 45000, close: true,
                    gravity: "top", // `top` or `bottom`
                    position: "left", // `left`, `center` or `right`
                    stopOnFocus: true, // Prevents dismissing of toast on hover
                    style: {background: 'linear-gradient(to right, #4b44bc, #8181be)'},
                    onClick: () => {} // Callback after click
                }).showToast();
            }
            setTimeout(() => {
                var fields = PROMPTS.get_data(thisClass);
                var voice = fields.find((row)=>row.type=='voice');
                if(voice) {
                    voice.cost = (voice.cost == '')?0:voice.cost;
                    // voiceRecord.meta_tag = voice.steptitle;
                    // voiceRecord.duration = parseFloat((voice.duration == '')?'20':voice.duration);
                    // popupCart.addAdditionalPrice(voice.steptitle, parseFloat(voice.cost));
                }
                PROMPTS.init_events(thisClass);
            }, 300);
        }
    },
    change_submit_btn_text: (type) => {
        document.querySelectorAll('.popup_foot .button[data-react="continue"]  > span:first-child').forEach((span) => {
            switch(type.toLowerCase()) {
                case 'continue':
                    span.innerHTML = PROMPTS.i18n?.continue??'Continue';
                    span.parentElement.parentElement.dataset.current = 'continue';
                    break;
                case 'booknow':
                    span.innerHTML = PROMPTS.i18n?.booknow??'Book Now';
                    span.parentElement.parentElement.dataset.current = 'booknow';
                    break;
                case 'get_quotation':
                    span.innerHTML = PROMPTS.i18n?.get_quotation??'Get Quotation';
                    span.parentElement.parentElement.dataset.current = 'get_quotation';
                    break;
                default:
                    break;
            }
        });
    },
    filtering_dataset: (thisClass) => {
        PROMPTS.lastJson.product.custom_fields.forEach((field, i) => {
            if(field?.type == 'select' && field?.options) {
                if(
                    field.options[0] && field.options[0]?.cost && field.options[0].cost != ''
                ) {
                    PROMPTS.lastJson.product.custom_fields[i].options = [
                        {
                            image: '', thumb: '',
                            next: false, cost: '',
                            label: PROMPTS.i18n?.selecturservice??'Select Your Service'
                        },
                        ...field.options
                    ];
                }
            }
        });
    }
};
export default PROMPTS;