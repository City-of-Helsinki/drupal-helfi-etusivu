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
        const [year, month, day] = unpublishDateInput.value.split('-').map(Number);
        const existingDate = new Date(Date.UTC(year, month - 1, day));
        return newDate > existingDate;
      };

      // Toggle unpublish hint visibility.
      const toggleUnpublishHint = (show) => {
        const hintElement = form.querySelector('.news-item-unpublish-hint');
        if (hintElement) {
          hintElement.classList.toggle('is-hidden', !show);
        }
      };

      // Add months to a date. Clamping the day to the last valid day
      // of the target month.
      const addMonthsUtc = (inputDate, monthsToAdd) => {
        // Anchor the time at noon UTC to avoid DST surprises.
        const base = new Date(
          Date.UTC(inputDate.getUTCFullYear(), inputDate.getUTCMonth(), inputDate.getUTCDate(), 12, 0, 0),
        );
        // JavaScript will automatically clamp the day to the last valid day
        // of the target month.
        base.setUTCMonth(base.getUTCMonth() + monthsToAdd);
        return base;
      };

      // Set the unpublish date to the given date plus 11 months.
      const setUnpublishDate = (date, isInitialLoad = false) => {
        if (!(date instanceof Date)) return;
        const unpublishDate = addMonthsUtc(
          new Date(
            Date.UTC(
              date.getUTCFullYear(),
              date.getUTCMonth(),
              date.getUTCDate(),
              date.getUTCHours(),
              date.getUTCMinutes(),
              date.getUTCSeconds(),
            ),
          ),
          11,
        );

        // Format the date as YYYY-MM-DD.
        const year = unpublishDate.getUTCFullYear();
        const month = String(unpublishDate.getUTCMonth() + 1).padStart(2, '0');
        const day = String(unpublishDate.getUTCDate()).padStart(2, '0');

        // Update the unpublish date if it should be updated.
        if (shouldUpdateUnpublishDate(unpublishDate)) {
          if (unpublishDateInput) {
            unpublishDateInput.value = `${year}-${month}-${day}`;
            const ev = new Event('change', { bubbles: true });
            ev.programmatic = true;
            unpublishDateInput.dispatchEvent(ev);
          }
          if (unpublishTimeInput) {
            unpublishTimeInput.value = '01:00:00';
            unpublishTimeInput.dispatchEvent(new Event('change', { bubbles: true }));
          }
          if (!isInitialLoad) toggleUnpublishHint(true);
        }
      };

      // Handle publish_on date/time changes.
      if (publishDateInput && publishTimeInput) {
        const handlePublishDateChange = () => {
          if (statusCheckbox?.checked) {
            return; // Skip if already published
          }

          const date = publishDateInput.value;
          const time = publishTimeInput.value;

          if (date && time) {
            // Parse date in UTC.
            const [year, month, day] = date.split('-').map(Number);
            const [hours, minutes, seconds = 0] = time.split(':').map(Number);
            const utcDate = new Date(Date.UTC(year, month - 1, day, hours, minutes, seconds));

            // Set the unpublish date. The second argument is set to "false"
            // to indicate that this is not an initial load.
            setUnpublishDate(utcDate, false);
          }
        };

        // Add event listeners for publish_on date/time changes.
        publishDateInput.addEventListener('change', handlePublishDateChange);
        publishTimeInput.addEventListener('change', handlePublishDateChange);
      }

      // Handle status change. This is used to set the unpublish date
      // when publishing immediately.
      if (statusCheckbox) {
        const handleStatusChange = (e) => {
          // Only proceed for actual user interactions.
          if (!e.isTrusted) return;

          // Set a zero timeout to ensure the status
          // has been updated in the form.
          setTimeout(() => {
            if (statusCheckbox.checked) {
              // Clear publish date fields when publishing immediately.
              if (publishDateInput) publishDateInput.value = '';
              if (publishTimeInput) publishTimeInput.value = '';
              // Set the unpublish date if the status change was triggered
              // by a user interaction.
              if (e.type === 'click' || e.type === 'change') {
                setUnpublishDate(new Date(), false);
              }
            }
          }, 0);
        };

        // Listen to both click and change events for better compatibility.
        statusCheckbox.addEventListener('change', handleStatusChange);
        statusCheckbox.addEventListener('click', handleStatusChange);
      }

      // Handle updating news date changes. Only update unpublish date
      // when user manually changes the date.
      if (updateWidget) {
        const dateInput = updateWidget.querySelector('input[type="date"], input.js-date');
        if (dateInput) {
          // Handle date changes.
          const handleDateChange = (e) => {
            if (e.target.value && e.isTrusted) {
              setUnpublishDate(new Date(e.target.value), false);
            }
          };

          // Remove any existing event listeners to prevent duplicates.
          dateInput.removeEventListener('change', handleDateChange);
          dateInput.addEventListener('change', handleDateChange);
        }
      }

      // Handle "Add more" button for news updates.
      once('news-update-handler', 'input[name*="field_news_item_updating_news_news_update_add_more"]', context).forEach(
        (button) => {
          // Use mousedown instead of click to ensure the event is triggered
          // before the form is submitted.
          button.addEventListener('mousedown', (e) => {
            // Exit if the event is not trusted.
            if (!e.isTrusted) return;

            // Create a new date 11 months from now.
            const newDate = addMonthsUtc(new Date(), 11);

            // Only update if the new date is later than the existing one.
            if (!shouldUpdateUnpublishDate(newDate)) return;

            // Update the unpublish date.
            if (unpublishDateInput) {
              unpublishDateInput.value = newDate.toISOString().split('T')[0];
              unpublishDateInput.dispatchEvent(new Event('change', { bubbles: true }));
              toggleUnpublishHint(true);
            }
            if (unpublishTimeInput) {
              unpublishTimeInput.value = '01:00:00';
              unpublishTimeInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
          });
        },
      );

      // Only set initial unpublish date if it's not already set and there's
      // a published date or if the content is already published.
      if (unpublishDateInput && !unpublishDateInput.value) {
        const isContentPublished = statusCheckbox?.checked;

        // Published content gets its unpublish date set when
        // the status changes.
        if (isContentPublished) return;

        // Only set the unpublish date if we have both publish date and time.
        const hasPublishDateTime = publishDateInput?.value && publishTimeInput?.value;
        if (!hasPublishDateTime) return;

        // Set the initial unpublish date (11 months from publish date).
        const publishDateTime = new Date(`${publishDateInput.value}T${publishTimeInput.value}`);
        setUnpublishDate(publishDateTime, true);
      }

      // Hide the hint if the unpublish date is manually edited.
      if (unpublishDateInput) {
        unpublishDateInput.addEventListener('change', (e) => {
          if (!e.programmatic) toggleUnpublishHint(false);
        });
      }
    },
  };
})(Drupal, once);
