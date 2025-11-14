((Drupal) => {
  Drupal.behaviors.updatingNewsTableOfContents = {
    attach: function attach(context) {
      // Prevent running multiple times on the main document.
      if (
        context !== document ||
        window.updatingNewsTableOfContentsInitialized
      ) {
        return;
      }

      const tableOfContentsNewsUpdates = context.getElementById(
        'helfi-toc-table-of-contents-news-updates',
      );
      const tableOfContentsList = context.querySelector(
        '#helfi-toc-table-of-contents-list > ul',
      );

      // Bail if table of contents is not enabled or list is not found.
      if (!tableOfContentsNewsUpdates || !tableOfContentsList) {
        return;
      }

      // Bail if heading injector is missing.
      if (
        !Drupal.HeadingIdInjector ||
        !Drupal.HeadingIdInjector.injectedHeadings
      ) {
        return;
      }

      // The core table of contents functionality will create the list with
      // all injected headings, so we need to clear it first.
      tableOfContentsList.innerHTML = '';

      // Get all news update headings that have a TIME element as next sibling
      const newsHeadings = Array.from(
        Drupal.HeadingIdInjector.injectedHeadings,
      ).filter(
        ({ content }) =>
          content.matches('.component--news-update .component__title') &&
          content.nextElementSibling?.tagName === 'TIME',
      );

      // Process each news heading with date
      newsHeadings.forEach(({ content, anchorName }) => {
        // On updating news there is published date under the title,
        // which we want to display in the news item table of contents.
        let contentPublishDate = '';

        // Get the published date from the next sibling element.
        if (
          content.nextElementSibling &&
          content.nextElementSibling.tagName === 'TIME'
        ) {
          const contentPublishDateStamp = new Date(
            content.nextElementSibling.dateTime,
          );
          contentPublishDate = `${contentPublishDateStamp.getDate()}.${contentPublishDateStamp.getMonth() + 1}.${contentPublishDateStamp.getFullYear()}`;
        }

        // Only process H2 headings for the TOC.
        if (content.tagName === 'H2') {
          const listItem = context.createElement('li');
          listItem.classList.add('table-of-contents__item');

          // Add content publish date and its wrapper
          // to list items only if they exist.
          if (contentPublishDate) {
            const publishDate = context.createElement('time');
            publishDate.dateTime = content.nextElementSibling.dateTime;
            publishDate.textContent = contentPublishDate;
            listItem.appendChild(publishDate);
          }

          // Create and append the link.
          const link = context.createElement('a');
          link.classList.add('table-of-contents__link');
          link.href = `#${anchorName || content.id}`;
          link.textContent = content.textContent.trim();
          listItem.appendChild(link);

          // Add to TOC.
          tableOfContentsList.appendChild(listItem);
        }
      });

      // Bail if Drupal.tableOfContents is missing.
      if (!Drupal.tableOfContents || !Drupal.tableOfContents.updateTOC) {
        return;
      }

      // Update the TOC visibility.
      Drupal.tableOfContents.updateTOC(tableOfContentsNewsUpdates);

      // Mark as initialized.
      window.updatingNewsTableOfContentsInitialized = true;
    },
  };
})(Drupal);
