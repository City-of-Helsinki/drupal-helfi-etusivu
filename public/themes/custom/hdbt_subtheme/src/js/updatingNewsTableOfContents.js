/**
 * Table of Contents for news updates.
 *
 * This functionality enhances the default table of contents by creating a
 * specialized version that only shows news update entries. Each entry displays
 * the publication date next to the news title.
 *
 * This functionality uses the table of contents list created by
 * the core tableOfContents.js (helfi_toc).
 */

/**
 * Get all news update headings that have a TIME element as next sibling.
 *
 * @param {Array} headings - Array of heading objects to filter.
 * @returns {Array} Filtered array of news update headings with time elements.
 */
const newsHeadings = (headings) => {
  return headings.filter(({ content }) => {
    if (!content) {
      return false;
    }

    const isNewsHeading = content.matches(
      '.component--news-update .component__title',
    );
    const hasTimeSibling = content.nextElementSibling?.tagName === 'TIME';

    return isNewsHeading && hasTimeSibling;
  });
};

/**
 * Create an LI element with the publication date.
 *
 * @param {Object} params - Object containing content and anchor information.
 * @param {HTMLElement} params.content - The heading element being processed.
 * @param {string} params.anchorName - The ID to use for the anchor link.
 * @returns {HTMLLIElement} The created list item with date and link.
 */
const createNewsListItem = ({ content, anchorName }) => {
  const listItem = document.createElement('li');
  listItem.classList.add('table-of-contents__item');

  let contentPublishDate = '';
  const timeElement = content.nextElementSibling;

  if (timeElement?.tagName === 'TIME') {
    const contentPublishDateStamp = new Date(timeElement.dateTime);
    contentPublishDate = `${contentPublishDateStamp.getDate()}.${
      contentPublishDateStamp.getMonth() + 1
    }.${contentPublishDateStamp.getFullYear()}`;
  }

  // Add content publish date and its wrapper to list items only if they exist.
  if (contentPublishDate) {
    const publishDate = document.createElement('time');
    publishDate.dateTime = timeElement.dateTime;
    publishDate.textContent = contentPublishDate;
    listItem.appendChild(publishDate);
  }

  const link = document.createElement('a');
  link.classList.add('table-of-contents__link');
  link.href = `#${anchorName || content.id}`;
  link.textContent = content.textContent.trim();
  listItem.appendChild(link);

  return listItem;
};

/**
 * Drupal behavior to initialize the updating news table of contents.
 *
 * @param {Object} Drupal - Drupal object.
 */
((Drupal) => {
  Drupal.behaviors.updatingNewsTableOfContents = {
    attach(context) {
      // Only run once when the full document is loaded, not during AJAX updates
      // or if we've already initialized the table of contents.
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

      // Stop if the required table of contents elements don't exist in the DOM.
      if (!tableOfContentsNewsUpdates || !tableOfContentsList) {
        return;
      }

      // Stop if there are no headings available to process.
      if (!Drupal.tableOfContents?.getInjectedHeadings()?.length) {
        return;
      }

      // Get all news update headings that have a TIME element as next sibling.
      const headings = newsHeadings(
        Drupal.tableOfContents.getInjectedHeadings(),
      );

      // Return if no news headings are found.
      if (!headings.length) {
        return;
      }

      // Build the table of contents.
      Drupal.tableOfContents.buildList({
        tocElement: tableOfContentsNewsUpdates,
        tocListElement: tableOfContentsList,
        headings: headings,
        createListItem: createNewsListItem,
      });

      // Set a flag to prevent duplicate initialization.
      window.updatingNewsTableOfContentsInitialized = true;
    },
  };
})(Drupal);
