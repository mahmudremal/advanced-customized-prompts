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
          width: 600,
          didOpen: async () => {
            document.querySelectorAll('#sos_import').forEach((button) => {
              button.addEventListener('change', (event) => {
                const files = event.target.files;
                EximClass.fileContents = files;
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
                event.preventDefault();
                event.stopPropagation();
                
                if(EximClass?.fileContents && EximClass.fileContents.length >= 1) {
                  /**
                   * Initializing form data.
                   */
                  var formdata = new FormData(form);
                  // formdata.append('action', 'sospopsproject/ajax/import/bulks');
                  // formdata.append('_nonce', thisClass.ajaxNonce);
                  
                  // [...EximClass.fileContents].forEach((file) => {
                  //   // EximClass.readFile(file).then((file_content) => {
                  //   //   console.log(file_content);
                  //   //   EximClass.fileContents.push(file_content);
                  //   //   // Send Single Bulk data to server.
                  //   // });
  
                  //   /**
                  //    * Adding file to request.
                  //    */
                  //   formdata.append('csv', file, file?.name);
                  // });
                  
                  /**
                   * Send files to server
                   */
                  // const json = Array.from(formdata).reduce((obj, [k, v]) => ({...obj, [k]: v}), {});
                  // console.log('formdata', formdata, json);
                  thisClass.sendToServer(formdata);
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
      // ! 
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
      }
    });
  }
  import_form_template(thisClass) {
    var message = 'Select a valid JSON file. Make sure you upload the correct file either it could harm your database that might recover manually by expert.';
    var tabs = [
      {
        name: 'pops',
        desc: message,
        tab:  'Popup',
      },
      {
        name: 'cats',
        desc: message,
        tab:  'Category',
      },
      {
        name: 'areas',
        desc: message,
        tab:  'Areas',
      },
      {
        name: 'services',
        desc: message,
        tab:  'Service',
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
          <h4 class="h4">${row.desc}</h4>
          <form class="exim_popup__content__form" action="" method="post">
            <input type="hidden" name="action" value="sospopsproject/ajax/import/bulks">
            <input type="hidden" name="_nonce" value="${thisClass.ajaxNonce}">
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
}
export default Exim;