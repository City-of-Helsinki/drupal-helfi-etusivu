(function (drupalSettings) {
  'use strict';

  Drupal.behaviors.debugStatus = {

    getDebugStatus: function (element) {
      const project = element.dataset.project;
      const environment = element.dataset.environment;

      const apiUrl = `${drupalSettings.path.baseUrl}${drupalSettings.path.pathPrefix}admin/dashboard/api-proxy?project=${project}&environment=${environment}`;

      fetch(apiUrl, {
        method: 'GET',
        headers: new Headers(),
        redirect: 'follow'
      })
        .then(async response => {
            const resultObj = JSON.parse(await response.text());
            const childObject = element.querySelector('.details-wrapper');

            if (resultObj.status) {
              childObject.innerHTML = resultObj.content;
            }

            if (resultObj.message && response.status > 200) {
              childObject.innerText = resultObj.message;
              childObject.style = 'color: red';
            }
          }
        );
    },

    attach: function (context, settings) {
      context.querySelectorAll('[data-environment]').forEach(this.getDebugStatus);
    }
  };

})(drupalSettings);
