import { Button } from 'hds-react';
import { useEffect, useState } from 'react';

import IndexFields from '../../enum/IndexFields';
import SearchComponents from '../../enum/SearchComponents';
import { setParams } from '../../helpers/Params';
import getQuery from '../../helpers/Query';
import { useLanguageQuery } from '../../hooks/useLanguageQuery';
import OptionType from '../../types/OptionType';

type SearchStateItem = {
  aggregations?: any;
  value: OptionType[];
};

type Props = {
  initialized: boolean;
  searchState: {
    [key: string]: SearchStateItem;
  };
  setQuery: Function;
};

export const ComponentMap = {
  [SearchComponents.TOPIC]: `${IndexFields.FIELD_NEWS_ITEM_TAGS}.keyword`,
  [SearchComponents.NEIGHBOURHOODS]: `${IndexFields.FIELD_NEWS_NEIGHBOURHOODS}.keyword`,
  [SearchComponents.NEWS_GROUPS]: `${IndexFields.FIELD_NEWS_GROUPS}.keyword`,
};

export const SubmitButton = ({ initialized, searchState, setQuery }: Props) => {
  const [mounted, setMounted] = useState<boolean>(false);
  const languageFilter = useLanguageQuery();

  const onClick = () => {
    setQuery(getQuery(searchState, languageFilter));
    setParams(searchState);
  };

  useEffect(() => {
    if (initialized && !mounted) {
      setQuery(getQuery(searchState, languageFilter));
      setMounted(true);
    }
  }, [getQuery, initialized, mounted, setMounted, setQuery]);

  return (
    <Button
      className='news-form__submit-button'
      disabled={!initialized}
      type='submit'
      onClick={onClick}
      variant='primary'
    >
      {Drupal.t('Filter')}
    </Button>
  );
};

export default SubmitButton;
