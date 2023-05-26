import IndexFields from '../enum/IndexFields';
import SearchComponents from '../enum/SearchComponents';
import { BooleanQuery, TermsQuery } from '../types/BooleanQuery';
import OptionType from '../types/OptionType';

type SearchState = {
  [key: string]: {
    aggregations?: any;
    value: OptionType[];
  };
};

const ComponentMap = {
  [SearchComponents.TOPIC]: `${IndexFields.NEWS_TAGS}`,
  [SearchComponents.NEIGHBOURHOODS]: `${IndexFields.NEIGHBOURHOODS}`,
  [SearchComponents.NEWS_GROUPS]: `${IndexFields.NEWS_GROUPS}`,
};

const getQuery = (searchState: SearchState, languageFilter: any) => {
  const must: TermsQuery[] = [];
  let query: BooleanQuery = {
    bool: {
      filter: languageFilter.bool.filter,
    },
  };

  Object.keys(ComponentMap).forEach((key: string) => {
    const state = searchState[key] || null;

    if (state && state.value && state.value.length) {
      must.push({
        terms: {
          [ComponentMap[key]]: state.value.map((value: OptionType) => value.value),
        },
      });
    }

    if (must.length) {
      query.bool.must = must;
    }
  });

  let result = {
    query: query,
    value: '',
  };

  if (query?.bool?.must?.length) {
    result.value = JSON.stringify(query.bool.must);
  }

  return result;
};

export default getQuery;
