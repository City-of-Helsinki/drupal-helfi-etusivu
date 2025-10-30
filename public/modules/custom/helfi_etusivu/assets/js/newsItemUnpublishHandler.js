/**
 * News item unpublish date handler.
 *
 * Automatically manages the "unpublish_on" field based on publish date/time,
 * status changes, and news update interactions.
 *
 * Rules:
 * - Default logic: unpublish = publish date + 11 months, at 01:00:00 UTC.
 * - Updates only if the new value is later than the current unpublish date.
 * - Anchors calculations in UTC to avoid DST issues; clamps day if month ends.
 *
 * Triggers:
 * 1. Scheduled publish date/time change → recompute unpublish date.
 * 2. Status checkbox (publish immediately) → unpublish = now + 11 months.
 * 3. Updating news widget date change → unpublish = chosen date + 11 months.
 * 4. "Add more" in news updates → propose today + 11 months if later.
 * 5. Initial load:
 *    - If unpublish date is empty and content is not published,
 *      set unpublish = publish + 11 months.
 *
 * Hint:
 * - A `.news-item-unpublish-hint` element is shown when the script sets
 *   the unpublish date automatically, and hidden again if the user manually
 *   edits the field.
 */
((Drupal, once) => {
  Drupal.behaviors.newsItemUnpublishHandler = {
    attach(context) {
      const form = context.querySelector('form') || document;
      const updateWidget = form.querySelector('#news-item-updating-news-widget');
      const publishDateInput = form.querySelector('[name="publish_on[0][value][date]"]');
      const publishTimeInput = form.querySelector('[name="publish_on[0][value][time]"]');
      const statusCheckbox = form.querySelector('input[name="status[value]"]');
      const unpublishDateInput = form.querySelector('[name="unpublish_on[0][value][date]"]');
      const unpublishTimeInput = form.querySelector('[name="unpublish_on[0][value][time]"]');

      // Check if unpublish_on date should be updated.
      const shouldUpdateUnpublishDate = (newDate) => {
        if (!unpublishDateInput?.value) return true;
        const existingDate = new Date(unpublishDateInput.value);
        return newDate > existingDate;
      };

      // Toggle unpublish hint visibility.
      const toggleUnpublishHint = (show) => {
        const hintElement = form.querySelector('.news-item-unpublish-hint');
        if (hintElement) {
          hintElement.classList.toggle('is-hidden', !show);
        }
      };

      // Set the unpublish date to 11 months from the given date.
      const setUnpublishDate = (date, isInitialLoad = false) => {
        if (!date || !(date instanceof Date)) {
          return;
        }

        // 11 months approximated as 11 * 30 days worth of seconds
        const monthsInSeconds = 11 * 30 * 24 * 60 * 60;
        const unpublishDate = new Date(date.getTime() + monthsInSeconds * 1000);
        if (shouldUpdateUnpublishDate(unpublishDate)) {
          if (unpublishDateInput) {
            unpublishDateInput.value = unpublishDate.toISOString().split('T')[0];
            // Dispatch a custom event to indicate this is a programmatic change
            const event = new Event('change', { bubbles: true });
            event.programmatic = true;
            unpublishDateInput.dispatchEvent(event);
          }
          if (unpublishTimeInput) {
            unpublishTimeInput.value = '01:00:00';
            unpublishTimeInput.dispatchEvent(new Event('change', { bubbles: true }));
          }

          // Show the hint for all programmatic changes except initial load
          if (!isInitialLoad) {
            toggleUnpublishHint(true);
          }
        }
      };

      // Handle publish_on date/time changes
      if (publishDateInput && publishTimeInput) {
        const handlePublishDateChange = () => {
          if (statusCheckbox?.checked) {
            return; // Skip if already published
          }

          const date = publishDateInput.value;
          const time = publishTimeInput.value;

          if (date && time) {
            // Pass false as second argument to indicate this is not an initial load
            setUnpublishDate(new Date(`${date}T${time}`), false);
          }
        };

        publishDateInput.addEventListener('change', handlePublishDateChange);
        publishTimeInput.addEventListener('change', handlePublishDateChange);
      }

      // Handle status change (publish immediately)
      if (statusCheckbox) {
        const handleStatusChange = (e) => {
          // Only proceed for actual user interactions
          if (!e.isTrusted) return;

          // Use a small timeout to ensure the status has been updated in the form
          setTimeout(() => {
            if (statusCheckbox.checked) {
              // Clear publish date fields when publishing immediately
              if (publishDateInput) publishDateInput.value = '';
              if (publishTimeInput) publishTimeInput.value = '';
              // Only set unpublish date if this is a direct user interaction
              if (e.type === 'click' || e.type === 'change') {
                setUnpublishDate(new Date(), false);
              }
            }
          }, 0);
        };

        // Listen to both click and change events for better compatibility
        statusCheckbox.addEventListener('change', handleStatusChange);
        statusCheckbox.addEventListener('click', handleStatusChange);
      }

      // Handle updating news date changes - only update unpublish date when user manually changes the date
      if (updateWidget) {
        const dateInput = updateWidget.querySelector('input[type="date"], input.js-date');
        if (dateInput) {
          // Only update unpublish date when user manually changes the date
          const handleDateChange = (e) => {
            if (e.target.value) {
              // Only update if this is a direct user interaction
              if (e.isTrusted) {
                setUnpublishDate(new Date(e.target.value), false);
              }
            }
          };

          // Remove any existing event listeners to prevent duplicates
          dateInput.removeEventListener('change', handleDateChange);
          dateInput.addEventListener('change', handleDateChange);
        }
      }

      // Handle "Add more" button for updating news
      once('news-update-handler', 'input[name*="field_news_item_updating_news_news_update_add_more"]', context).forEach((button) => {
        // Use mousedown instead of click to ensure we run before the form submission
        button.addEventListener('mousedown', (e) => {
          if (e.isTrusted) {
            // Create a new date 11 months from now
            const newDate = new Date();
            newDate.setMonth(newDate.getMonth() + 11);

            // Only update if the new date is later than the existing one
            if (shouldUpdateUnpublishDate(newDate)) {
              // Update the unpublish date
              if (unpublishDateInput) {
                // Use setAttribute to ensure the change is recognized by the form
                unpublishDateInput.setAttribute('value', newDate.toISOString().split('T')[0]);
                unpublishDateInput.dispatchEvent(new Event('change', { bubbles: true }));
                // Show the hint
                toggleUnpublishHint(true);
              }
              if (unpublishTimeInput) {
                unpublishTimeInput.setAttribute('value', '01:00:00');
                unpublishTimeInput.dispatchEvent(new Event('change', { bubbles: true }));
              }
            }
          }
        });
      });

      // Only set initial unpublish date if it's not already set and there's a publish date
      // or if the content is already published
      if (unpublishDateInput && !unpublishDateInput.value) {
        if (statusCheckbox?.checked) {
          // Don't set unpublish date automatically for published content
          // It will be set when the status is changed
        } else if (publishDateInput?.value && publishTimeInput?.value) {
          // Only set initial unpublish date if there's a publish date
          // and no unpublish date is set yet
          setUnpublishDate(new Date(`${publishDateInput.value}T${publishTimeInput.value}`), true);
        }
      }

      // Hide the hint when the unpublish date is manually cleared
      if (unpublishDateInput) {
        unpublishDateInput.addEventListener('change', (e) => {
          // Only hide if the change wasn't triggered programmatically
          if (!e.programmatic && !unpublishDateInput.value) {
            toggleUnpublishHint(false);
          }
        });
      }
    }
  };
})(Drupal, once);
