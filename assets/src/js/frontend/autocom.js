/**
 * Autocomplete classes for SoS project.
 */

  export class AutoCom {
    constructor(thisClass) {
        this.setup_hooks(thisClass);
    }
    setup_hooks(thisClass) {
      this.sos_hero__searchable(thisClass);
    }
    sos_hero__searchable(thisClass) {
      const AutoComClass = this;
      var formdata = new FormData();
      formdata.append('action', 'sospopsproject/ajax/suggested/categories');
      formdata.append('category_id', AutoComClass.lastCategoryID);
      formdata.append('_nonce', thisClass.ajaxNonce);
      thisClass.sendToServer(formdata);
      document.body.addEventListener('suggested_categories_success', (event) => {
        AutoComClass.storedList = thisClass.lastJson?.terms??[];
        document.querySelectorAll('.sos_hero__searchable__select:not([data-select-handled])').forEach((select) => {
          select.dataset.selectHandled = true;
          AutoComClass.autocomplete = new thisClass.SlimSelect({
            select: select,
            settings: {
                // alwaysOpen: false,
                showSearch: true,
                openPosition: 'auto',
                searchHighlight: true,
                placeholderText: thisClass.i18n?.whtsnurtdlist??'What’s on your to-do list?',
                searchText: thisClass.i18n?.srctext??'Sorry nothing to see here',
                searchPlaceholder: thisClass.i18n?.srcurtdlist??'Search your to-do list.'
            },
            events: {
                beforeChange: (newVal, oldVal) => {
                    if(newVal[0] && newVal[0]?.value) {
                        document.querySelectorAll('.sos_hero__wrap').forEach((el) => el.action = newVal[0]?.value??'');
                    }
                },
                search: async (search, callback) => await AutoComClass.fetchSearchable(search, callback, thisClass),
                // search: function (search, callback) {
                //   formdata.append('search', search);
                //   thisClass.Post.sendToServer(formdata, thisClass).then((response) => {
                //     console.log(response);
                //     callback(response.terms);
                //   }).catch(err => {
                //     console.error("Error:", err);
                //     callback(thisClass.lastJson?.terms??[]);
                //   });
                // },
            },
            data: AutoComClass.get_stored_list(),
          });
        });
      });
      
    }
    async fetchSearchable(search, callback, thisClass) {
      const AutoComClass = this;
      if (search.length < 3) {
        const msg = 'Need 3 characters';
        if (typeof callback === 'function') {callback(msg);}
        return AutoComClass.get_stored_list();
      }
      return await fetch(
        thisClass.ajaxUrl + '?' + 
        [
          ['action', 'sospopsproject/ajax/suggested/categories'],
          ['search', search], ['_nonce', thisClass.ajaxNonce],
          ['per_page', 10], ['order', 'desc']
        ].map(row => `${row[0]}=${row[1]}`).join('&')
      )
      .then(response => {
        return response.json()
      })
      .then(json => {
        const terms = json.data?.terms??[];
        // AutoComClass.autocomplete.setData([...terms]);
        AutoComClass.storedList = terms;
        if (typeof callback === 'function') {callback(terms);}
        return terms;
      })
      .catch(error => {
        const terms = thisClass.lastJson?.terms??[];
        if (typeof callback === 'function') {callback(terms);}
        return terms;
      });
    }
    get_stored_list() {
      return this.storedList.filter(row => row?.options && row.options.length >= 1);
    }
  }

