'use strict';

(function (Drupal, once) {
  Drupal.behaviors.updating_news_table_of_contents = {
    attach: function attach() {

      const tableOfContentsNewsUpdates = document.getElementById('helfi-toc-table-of-contents-news-updates');

      // Bail if table of contents is not enabled.
      if (!tableOfContentsNewsUpdates) {
        return;
      }

      const tableOfContentsList = document.querySelector('#helfi-toc-table-of-contents-list > ul');
      const mainContent = document.querySelector('main.layout-main-wrapper');
      const reservedElems = document.querySelectorAll('[id]');
      reservedElems.forEach(function (elem) {
        Drupal.tableOfContents.reservedIds.push(elem.id);
      });
      let exclusions = Drupal.tableOfContents.exclusions();

      // Add exclusions for the news updates table of contents. NOTICE: The text paragraph is the only one that
      // has paragraph as a prefix, so it might look a bit silly compared to the other selectors.
      exclusions +=
        ':not(.components--upper *)' +
        ':not(.component--remote-video *)' +
        ':not(.component--paragraph-text *)' +
        ':not(.component--banner *)' +
        ':not(.component--image *)' +
        ':not(.block--news-of-interest *)' +
        ':not(#helfi-toc-table-of-contents-news-updates *)';

      // Craft table of contents for news item.
      once('updating-news-table-of-contents', Drupal.tableOfContents.titleComponents(exclusions).join(','), mainContent)
        .forEach(function (content) {

          const { nodeName, anchorName} = Drupal.tableOfContents.createTableOfContentElements(content, []);

          // On updating news there is published date under the title that we want to display in the
          // table of contents news update version. For normal table of contents this remains empty.
          let contentPublishDate = '';

          if (tableOfContentsNewsUpdates && content.nextSibling && content.nextElementSibling.nodeName === 'TIME') {
            let contentPublishDateStamp = new Date(content.nextElementSibling.dateTime);
            contentPublishDate = `${contentPublishDateStamp.getDate()}.${contentPublishDateStamp.getMonth() + 1}.${contentPublishDateStamp.getFullYear()}`;
          }

          // Create table of contents if component is enabled.
          if (tableOfContentsList && nodeName === 'h2') {
            let listItem = document.createElement('li');
            listItem.classList.add('table-of-contents__item');

            // Add content publish date and its wrapper to list items only if they exist.
            if (contentPublishDate) {
              let publishDate = document.createElement('time');
              publishDate.dateTime = content.nextElementSibling.dateTime;
              publishDate.textContent = contentPublishDate;
              listItem.appendChild(publishDate);
            }

            let link = document.createElement('a');
            link.classList.add('table-of-contents__link');
            link.href = `#${anchorName}`;
            link.textContent = content.textContent.trim();

            listItem.appendChild(link);
            tableOfContentsList.appendChild(listItem);
          }
        }
      );

      if (tableOfContentsNewsUpdates) {
        Drupal.tableOfContents.updateTOC(tableOfContentsNewsUpdates);
      }
    },
  };
})(Drupal, once);
