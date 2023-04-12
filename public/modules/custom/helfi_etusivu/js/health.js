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

    const apiUrl = `${drupalSettings.path.baseUrl}${drupalSettings.path.pathPrefix}admin/dashboard/health/json?project=${project}&environment=${environment}`;

    fetch(apiUrl, requestOptions)
      .then(response => response.text())
      .then(result => {
          const resultObj = JSON.parse(result);

          if (resultObj.status) {
            element.innerText = Drupal.t('Environment is up');
            element.style = 'color: green';
          } else {
            element.innerText = Drupal.t('Environment is not responding');
            element.style = 'color: red';
          }
        }
      )
      .catch(error => console.log('error', error));
}
document.querySelectorAll('[data-environment]').forEach(getHealth);

})(drupalSettings);
