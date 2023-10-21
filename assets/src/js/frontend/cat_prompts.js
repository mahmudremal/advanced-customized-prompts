import icons from "./icons";

const CAT_PROMPTS = {
    loadedCategories: {},
    lastCategoryLink: false,
    lastCategoryID: false,
    init: (thisClass) => {
        document.querySelectorAll('.service_catlist__list__link:not([data-pops-handled])').forEach((el) => {
            el.dataset.popsHandled = true;
            el.addEventListener('click', (event) => {
                event.preventDefault();
                CAT_PROMPTS.lastCategoryLink = el.href;
                CAT_PROMPTS.lastCategoryID = el.dataset?.category??0;
                thisClass.Swal.fire({
                    title: false, width: 900,
                    showConfirmButton: false,
                    showCancelButton: false,
                    showCloseButton: true,
                    allowOutsideClick: false,
                    allowEscapeKey: true,
                    customClass: {popup: 'sos-catpops'},
                    showLoaderOnConfirm: true,
                    allowOutsideClick: () => !thisClass.Swal.isLoading(),
                    html: `<div class="sos-catpops__body">
                        <div class="spinner-material"></div><h3>${thisClass.i18n?.pls_wait??'Please wait...'}</h3>
                    </div>`,
                    // footer: '<a href="">Why do I have this issue?</a>',
                    didOpen: async () => {
                        var formdata = new FormData();
                        formdata.append('action', 'sospopsproject/ajax/search/category');
                        formdata.append('category_id', CAT_PROMPTS.lastCategoryID);
                        formdata.append('_nonce', thisClass.ajaxNonce);
                        thisClass.sendToServer(formdata);
                    },
                    preConfirm: async (login) => {return thisClass.prompts.on_Closed(thisClass);}
                }).then(async (result) => {
                    // if( result.isConfirmed ) {}
                })
            });
        });
    },
    load_template: (thisClass) => {
        CAT_PROMPTS.loadedCategories = thisClass.lastJson?.parent;
        document.querySelectorAll('.sos-catpops__body').forEach((popsBody) => {
            var services = CAT_PROMPTS.loadedCategories?.services;
            services = (services && typeof services === 'object')?services:[];
            popsBody.innerHTML = `
            <div class="sos-catpops__body__wrap">
                <div class="sos-catpops__body__row">
                    <div class="col-5">
                        <div class="sos-catpops__left__list">
                            ${(CAT_PROMPTS.loadedCategories?.childrens??[])?.map((cat, i) => `
                                <a class="sos-catpops__catlink" href="${cat?.url}" data-category="${cat?.term_id}" target="_self" data-count="${cat?.count??0}" data-parent="${cat?.parent??0}" data-index="${i}">
                                    <div class="sos-catpops__catitem">
                                        <div class="sos-catpops__catitem__image">
                                            ${((cat?.thumbnail??'').trim() != '')?(cat?.thumbnail??''):(icons?.blank)}
                                        </div>
                                        <div class="sos-catpops__catitem__label">
                                            ${cat?.name??''}
                                        </div>
                                    </div>
                                </a>
                            `).join('')}
                        </div>
                        <div class="sos-catpops__left__bottom">
                            <a class="sos-catpops__catlink" href="${CAT_PROMPTS.lastCategoryLink}" data-category="${CAT_PROMPTS.lastCategoryID}" target="_self" data-count="" data-parent="0" data-index="1">
                                <div class="sos-catpops__catitem">
                                    <div class="sos-catpops__catitem__image">
                                        ${icons.left}
                                    </div>
                                    <div class="sos-catpops__catitem__label">
                                        ${thisClass.i18n?.viewallservice??'View All Service'}
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="col-7">
                        <div class="sos-catpops__right">
                            <ul class="sos-catpops__right__list">
                                ${services?.map((post) => `
                                <li class="sos-catpops__right__list__item">
                                    <a class="sos-catpops__right__list__link" href="${post?.url??'#'}" target="_self">${post?.title??''}</a>
                                </li>
                                `).join('')}
                            </ul>
                        </div>
                    </div>
                </div>
            </div>`;
            setTimeout(() => {
                popsBody.querySelectorAll('.sos-catpops__left__list .sos-catpops__catlink:not([data-click-handled])').forEach((el) => {
                    el.dataset.clickHandled = true;
                    el.addEventListener('click', (event) => {
                        event.preventDefault();
                        if(CAT_PROMPTS.loadedCategories?.childrens[el.dataset.index]) {
                            var terms = CAT_PROMPTS.loadedCategories.childrens[el.dataset.index];
                            if(terms) {
                                var ground = popsBody.querySelector('.sos-catpops__right__list');
                                if(ground) {ground.innerHTML = CAT_PROMPTS.print_service_list(terms);}
                            }
                        }
                    });
                });
            }, 500);
        });
    },
    print_service_list: (term) => {
        return (term?.services??[])?.map((post) => `
            <li class="sos-catpops__right__list__item">
                <a class="sos-catpops__right__list__link" href="${post?.url??'#'}" target="_self">${post?.title??''}</a>
            </li>
        `).join('');
    },

    hero_autocomplete: (thisClass) => {
        // 
    }
};
export default CAT_PROMPTS;