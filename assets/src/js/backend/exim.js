/**
 * Export & Import Functionality on admin screen.
 */

import icons from "../frontend/icons";

class Exim {
  constructor(thisClass) {
    this.setup_hooks(thisClass);
  }
  setup_hooks(thisClass) {
    this.init_import_button(thisClass);
    this.init_import_events(thisClass);
  }
  init_import_button(thisClass) {
    const EximClass = this;EximClass.fileContents = [];
    document.querySelectorAll('#do_import').forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        thisClass.Swal.fire({
          position: 'center',
          title: thisClass.i18n?.bulk_import??'Bulk Import',
          html: EximClass.import_form_template(thisClass),
          showConfirmButton: false,
          allowOutsideClick: false,
          width: 600,
          didOpen: async () => {
            document.querySelectorAll('#sos_import').forEach((button) => {
              button.addEventListener('change', (event) => {
                const files = event.target.files;
                EximClass.fileContents = files;
              });
            });
            document.querySelectorAll('.clean_execution').forEach((button) => {
              button.addEventListener('click', (event) => {
                event.preventDefault();
                button.disabled = true;
                var counter = document.createElement('span');
                counter.classList.add('counter');
                counter.style.fontSize = '10px';
                counter.style.marginLeft = '5px';
                thisClass.Post.on(['event-progress', 'event-message'], counter, (event) => {
                  event = thisClass.Post.event(event);
                  if (event?.percentComplete && counter) {
                    counter.innerHTML = event.percentComplete.toFixed(0) + '%';
                  }
                });
                // , 'event-error'
                thisClass.Post.on(['event-finish'], counter, (event) => {
                  event = thisClass.Post.event(event);button.disabled = false;
                  setTimeout(() => {counter.remove();}, 3000);
                });
                button.appendChild(counter);
                var formdata = new FormData();
                formdata.append('action', 'sospopsproject/ajax/import/clean');
                formdata.append('taxonomy', button.dataset.taxonomy);
                formdata.append('clean', button.dataset.target);
                formdata.append('_nonce', thisClass.ajaxNonce);
                thisClass.Post.sendToServer(formdata, thisClass, {
                  // eventStream: true, url: thisClass.ajaxUrl+`?action=sospopsproject/ajax/import/clean&clean=${button.dataset.target}&taxonomy=${button.dataset.taxonomy}`
                });
              });
            });
            document.querySelectorAll('.exim_popup__tabs input[type=radio][name=tab]').forEach((radio) => {
              radio.addEventListener('change', (event) => {
                document.querySelectorAll('.exim_popup__content__single.visible').forEach((el) => el.classList.remove('visible'));
                if(radio?.checked) {
                  document.querySelectorAll('.exim_popup__content__single[data-index="' + radio.dataset.index + '"]').forEach((el) => el.classList.add('visible'));
                }
              });
            });
            document.querySelectorAll('.exim_popup__content__form').forEach((form) => {
              form.addEventListener('submit', (event) => {
                event.preventDefault();event.stopPropagation();
                if(EximClass?.fileContents && EximClass.fileContents.length >= 1) {
                  /**
                   * Initializing form data.
                   */
                  var formdata = new FormData(form);
                  formdata.append('action', 'sospopsproject/ajax/import/bulks');
                  formdata.append('_nonce', thisClass.ajaxNonce);
                  EximClass.mute_unmute_form(true);
                  
                  var args = {
                    // eventStream: true,
                  };
                  thisClass.Post.sendToServer(formdata, thisClass, args).then((response) => {
                    console.log('Success:', response);
                  }).catch(err => {
                    EximClass.mute_unmute_form(false);
                    console.error('Error:', err);
                  });
                }
              });
            });
          }
        });
      });
    });
  }
  readFile(file) {
    return new Promise(function(resolve, reject) {
      const reader = new FileReader();
      reader.onload = function(event) {
        const contents = event.target.result;
        resolve(contents);
      };
      reader.onerror = function(event) {
        const error = event.target.error;
        console.error("Error reading file:", error);
        reject(error);
      };
      reader.readAsText(file);
    });
  }
  init_import_events(thisClass) {
    const EximClass = this;
    document.body.addEventListener('sos_imports_response', (event) => {
      EximClass.lastJson = thisClass.lastJson;
      if(!EximClass.lastJson?.message) {EximClass.lastJson.message = [];}
      
      if(true) {
        var _has_message = (EximClass.lastJson.message.length >= 1);
        var _has_error = EximClass.lastJson.success.find((is_it) => is_it == false);
        var _has_success = EximClass.lastJson.success.find((is_it) => is_it == true);
        thisClass.Swal.fire({
          position: _has_message?'center':'top-end',
          icon: _has_message?(
            _has_error?'error':'success'
          ):'success',
          title: _has_message?(
            (_has_error && _has_success)?false:false
            // 'Import Session executed but seems some issue happens.'
          ):'Successfully Imported with no issue.',
          html: _has_message?EximClass.debug__template(thisClass):false,
          confirmButtonText: thisClass.i18n?.done??'Done',
          showConfirmButton: _has_message,
          timer: _has_message?false:4500
        });
      } else {
        /**
         * If Swal updates without removing import pops, then Unmute previously muted form.
         */
        EximClass.mute_unmute_form(false);
      }
    });
  }
  import_form_template(thisClass) {
    var message = 'Select a valid JSON file. Make sure you upload the correct file either it could harm your database that might recover manually by expert.';
    var tabs = [
      {
        name: 'mixed',
        desc: message,
        tab:  'Mixed',
        clean: 'mixed',
        taxonomy: 'n/a'
      },
      {
        name: 'services',
        desc: message,
        tab:  'Service',
        clean: 'services',
        taxonomy: 'n/a'
      },
      {
        name: 'pops',
        desc: message,
        tab:  'Popup',
        clean: 'pops',
        taxonomy: 'n/a'
      },
      {
        name: 'cats',
        desc: message,
        tab:  'Category',
        clean: 'terms',
        taxonomy: 'services'
      },
      {
        name: 'areas',
        desc: message,
        tab:  'Areas',
        clean: 'terms',
        taxonomy: 'area'
      },
    ];
    return `
    <div class="exim_popup">
      <div class="exim_popup__tabs" style="height: ${(60 * tabs.length)}px;">
        ${tabs.map((row, i) => `
          <input type="radio" id="tab${i}" name="tab" data-index="${i}" ${(i == 0)?`checked="true"`:''}>
          <label for="tab${i}">${row.tab}</label>
        `).join('')}
        <div class="exim_popup__marker">
          <div class="exim_popup__marker__top"></div>
          <div class="exim_popup__marker__bottom"></div>
        </div>
      </div>
      
      <div class="exim_popup__content">
        ${tabs.map((row, i) => `
        <div class="exim_popup__content__single ${(i == 0)?'visible':''}" data-index="${i}">
          <p class="h4">${row.desc}</p>
          <div class="exim_popup__content__actions">
            <a class="btn button link" href="${thisClass.config.buildPath}/csv/import-template-${row.name}.csv" download="Sample Format ${row.name}.csv">${thisClass.i18n?.sampleformat??'Sample format'}</a>
            <button class="btn button clean_execution" data-target="${row.clean}" data-taxonomy="${row.taxonomy}">${thisClass.i18n?.clean??'Clean'} ${row.name}</button>
          </div>
          <form class="exim_popup__content__form" action="" method="post">
            <input type="hidden" name="import_type" value="${row.name}">
            <input id="sos_import" type="file" name="sos_import" placeholder="" value="" accept=".csv" required="required">
            <input class="btn button submit" type="submit" value="Submit" />
          </form>
        </div>
        `).join('')}
      </div>

    </div>
    <style>${tabs.map((row, i) => `#tab${i}:checked ~ .exim_popup__marker{transform: translateY(calc(calc(50% / ${tabs.length}) * ${(i + 0)}));}`).join('')}</style>
    `;
  }
  debug__template(thisClass) {
    const EximClass = this;
    return `
    <ul class="debug_info">
      ${EximClass.lastJson.message.map((msg, i) => `<li data-index="${i}" data-status="${
        (EximClass.lastJson.success[i])?'success':'error'
      }">${msg}</li>`).join('')}
    <ul>`;
  }
  mute_unmute_form(mute = true) {
    const EximClass = this;
    document.querySelectorAll('.exim_popup__content__form').forEach((form) => {
      form.querySelectorAll('[type="submit"]').forEach((button) => {
        if(mute) {
          button.disabled = true;
        } else {
          button.removeAttribute('disabled');
        }
      });
    });
    document.querySelectorAll('.exim_popup').forEach((popup) => {
      if(mute) {
        popup.dataset.loading = true;
        var preloader = document.createElement('div');
        var loader = document.createElement('div');
        var counter = document.createElement('div');
        preloader.classList.add('preloader');
        counter.classList.add('counter');
        loader.classList.add('loader');
        counter.innerHTML = '0%';

        thisClass.Post.on(['event-progress'], counter, (event) => {
          event = thisClass.Post.event(event);
          if (event?.percentComplete) {
            counter.innerHTML = event.percentComplete.toFixed(0) + '%';
          }
        });
        thisClass.Post.on(['event-finish'], counter, (event) => {
          event = thisClass.Post.event(event);
          EximClass.mute_unmute_form(false);
          console.log(event);
          if (event?.json && event.json?.response) {
            if (event.json.response?.success && thisClass.lastJson?.success) {
              thisClass.lastJson.success = [...thisClass.lastJson?.success, ...event.json.response?.success];
            }
            if (event.json.response?.message && thisClass.lastJson?.message) {
              thisClass.lastJson.message = [...thisClass.lastJson?.message, ...event.json.response?.message];
            }
            if (event.json.response?.imported_data && thisClass.lastJson?.imported_data) {
              thisClass.lastJson.imported_data = [...thisClass.lastJson?.imported_data, ...event.json.response?.imported_data];
            }

            document.body.dispatchEvent(new Event('sos_imports_response'));
          }
        });
        
        loader.appendChild(counter);
        preloader.appendChild(loader);
        popup.appendChild(preloader);
      } else {
        popup.removeAttribute('data-loading');
        popup.querySelectorAll('.preloader').forEach(el => el.remove());
      }
    });
  }
}
export default Exim;