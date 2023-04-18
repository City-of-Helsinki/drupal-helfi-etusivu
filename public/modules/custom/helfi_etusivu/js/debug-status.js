(function (drupalSettings) {
  'use strict';

  document.querySelectorAll('[data-since]')
    .forEach((sinceCell) => {
        sinceCell.innerText = since(sinceCell.dataset.since);
      }
    );


  function getHealth(element) {
    var requestOptions = {
      method: 'GET',
      headers: new Headers(),
      redirect: 'follow'
    };

    const project = element.dataset.project;
    const environment = element.dataset.environment;

    const apiUrl = `${drupalSettings.path.baseUrl}${drupalSettings.path.pathPrefix}admin/dashboard/api-proxy?project=${project}&environment=${environment}`;

    fetch(apiUrl, requestOptions)
      .then(response => response.text())
      .then(result => {
          const resultObj = JSON.parse(result);
          const childObject = element.querySelector('.details-wrapper');

          if (resultObj.status) {
            childObject.innerHTML = resultObj.content;
          } else {
            childObject.innerText = Drupal.t('Environment is not responding');
            childObject.style = 'color: red';
          }
        }
      )
      .catch(error => console.log('error', error));
}
document.querySelectorAll('[data-environment]').forEach(getHealth);

})(drupalSettings);
