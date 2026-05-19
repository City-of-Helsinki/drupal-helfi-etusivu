enum AppSettings {
  ROOT_ID = 'helfi-etusivu-site-search',
  MIN_QUERY_LENGTH = 3,
  // Must stay <= QueryBuilder::KNN_MAX_SIZE (50) in helfi_search.
  SIZE = 10,
}

export default AppSettings;
