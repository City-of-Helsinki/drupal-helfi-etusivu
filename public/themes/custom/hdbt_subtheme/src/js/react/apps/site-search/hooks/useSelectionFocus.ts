import { useAtom } from 'jotai';
import { type RefObject, useEffect, useRef } from 'react';
import { selectionFocusAtom } from '../store';

export const FILTERS_CLOSE_BUTTON_CLASS = 'hdbt-search--react__filters-close-button';

export type SelectionFocusRefs = {
  tags: RefObject<Map<string, HTMLElement>>;
  filters: RefObject<HTMLDivElement | null>;
};

// The HDS Accordion close button ("Close Filter search results") sits inside
// the content region, which is hidden while the accordion is closed. Fall back
// to the accordion toggle in that case.
const getFiltersButton = (filters: HTMLElement | null) => {
  const closeButton = filters?.querySelector<HTMLElement>(`.${FILTERS_CLOSE_BUTTON_CLASS}`);

  if (closeButton?.getClientRects().length) {
    return closeButton;
  }

  return filters?.querySelector<HTMLElement>('button[aria-expanded]') ?? null;
};

type SearchState = { data: unknown; error: unknown };

// Removing a selection tag (or clearing them all) removes the focused button
// from the DOM. Once the search has settled, move focus to:
// 1. the results heading, if there are results
// 2. the tag before the removed one
// 3. the tag after the removed one
// 4. the filters accordion button, if no tags are left
const useSelectionFocus = (
  resultsHeadingRef: RefObject<HTMLElement | null>,
  { tags, filters }: SelectionFocusRefs,
  {
    data,
    error,
    hasResults,
    isValidating,
    isValidQuery,
  }: SearchState & {
    hasResults: boolean;
    isValidating: boolean;
    isValidQuery: boolean;
  },
) => {
  const [pendingFocus, setPendingFocus] = useAtom(selectionFocusAtom);
  const previousRef = useRef<SearchState>({ data, error });
  const startRef = useRef<SearchState | null>(null);

  useEffect(() => {
    const previous = previousRef.current;
    previousRef.current = { data, error };

    if (pendingFocus === null) {
      startRef.current = null;
      return;
    }

    // SWR reports isValidating as false on the first render after the key
    // changes, and keepPreviousData keeps returning the old data until the
    // new response arrives. Wait until data or error differ from what was
    // shown before the selection changed.
    startRef.current ??= previous;
    const isSettled =
      !isValidQuery ||
      (!isValidating && (data !== startRef.current.data || (error !== undefined && error !== startRef.current.error)));

    if (!isSettled) {
      return;
    }

    startRef.current = null;
    setPendingFocus(null);

    const heading = resultsHeadingRef.current;

    if (hasResults && heading) {
      heading.setAttribute('tabindex', '-1');
      heading.focus();
      return;
    }

    const tag = pendingFocus.bundle ? tags.current.get(pendingFocus.bundle) : undefined;

    (tag ?? getFiltersButton(filters.current))?.focus();
  }, [
    pendingFocus,
    data,
    error,
    hasResults,
    isValidating,
    isValidQuery,
    resultsHeadingRef,
    tags,
    filters,
    setPendingFocus,
  ]);
};

export default useSelectionFocus;
