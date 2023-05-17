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
  [SearchComponents.NEIGHBOURHOODS]: `${IndexFields.NEIGHBOURHOORDS}`,
  [SearchComponents.NEWS_GROUPS]: `${IndexFields.NEWS_GROUPS}`,
};

const getQuery = (searchState: SearchState, languageFilter: any) => {
  let query: BooleanQuery = {
    bool: {
      must: [],
      filter: languageFilter.bool.filter,
    },
  };

  Object.keys(ComponentMap).forEach((key: string) => {
    const state = searchState[key] || null;
    const should: TermsQuery[] = [];

    if (state && state.value && state.value.length) {
      should.push({
        terms: {
          [ComponentMap[key]]: state.value.map((value: OptionType) => value.value),
        },
      });
    }

    if (should.length && query.bool?.must) {
      query.bool.must.push({ bool: { should: should, minimum_should_match: 1 } });
    }
  });

  let result = {
    query: query,
    value: '',
  };

  if (query.bool?.must?.length) {
    result.value = JSON.stringify(query.bool.must);
  }

  return result;
};

export default getQuery;
