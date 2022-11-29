import SearchComponents from '../enum/SearchComponents';
import type SearchState from '../types/SearchState';
import NewsSearchParams from './NewsSearchParams';

const MASK_KEYS = [
  SearchComponents.TOPIC,
  SearchComponents.NEIGHBOURHOODS,
  SearchComponents.NEWS_GROUPS,
  SearchComponents.RESULTS,
];

export const getInitialValues = () => {
  const params = new NewsSearchParams(window.location.search);

  return params.toInitialValue();
};

const updateParams = (
  searchState: SearchState,
  searchParams: NewsSearchParams = new NewsSearchParams(),
  mask: string[] | null = null
) => {
  const keyArray = mask || MASK_KEYS;

  keyArray.forEach((key: string) => {
    if (!searchState[key]?.hasOwnProperty('value') || !keyArray.includes(key)) {
      return;
    }

    const value = searchState[key].value;
    if (Array.isArray(value)) {
      const transformedValue = value.map((selection) => selection.value);
      searchParams.set(key, JSON.stringify(transformedValue));
    } else if (value) {
      searchParams.set(key, value);
    } else {
      searchParams.delete(key);
    }
  });

  return searchParams;
};

/**
 * Update URL parameters.
 * @param searchState current searchState
 * @param mask
 * @returns
 */
export const setParams = (searchState: any, mask: string[] | null = null) => {
  const searchParams = new NewsSearchParams();
  const transformedParams = updateParams(searchState, searchParams);

  try {
    const allParamsString = transformedParams.toString();

    // If resulting string is the same as current one, do nothing.
    if (window.location.search === allParamsString) {
      return;
    }

    const newUrl = new URL(window.location.pathname, window.location.origin);
    newUrl.search = allParamsString;
    window.history.pushState({}, '', newUrl.toString());
  } catch (e) {
    console.warn('Error setting URL parameters.');
  }
};

export const clearParams = () => {
  const newUrl = new URL(window.location.pathname, window.location.origin);
  window.history.pushState({}, '', newUrl.toString());
};
