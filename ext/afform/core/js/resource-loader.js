"use strict";
/* jshint esversion: 6 */
/* jshint browser: true */
/* global console */

class CrmResourceLoader {

  constructor() {
    this.uiRoot = document.createElement('div');
  }

  addResources(list) {
    const self = this;
    list.forEach(res => {
      const f = this['add' + res.t];
      if (!f) {
        throw 'Resource type ' + res.t + ' is unrecognized - no adder function found.';
      }
      f.apply(self, [res.u]);
    });
  }

  addMarkup(markup) {
    this.uiRoot.innerHTML = this.uiRoot.innerHTML + markup;
  }

  addScriptUrl(url) {
    console.log('TODO: addScriptUrl: ' + url);
    // const po = document.createElement('script');
    // po.type = 'text/javascript';
    // po.async = true;
    // po.src = url;
    // const s = document.getElementsByTagName('script')[0];
    // s.parentNode.insertBefore(po, s);
  }

  addStyleUrl(url) {
    console.log('TODO: addStyleUrl: ' + url);
  }

}
