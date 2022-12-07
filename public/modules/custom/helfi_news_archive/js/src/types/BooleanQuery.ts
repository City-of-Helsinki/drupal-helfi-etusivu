export type TermQuery = {
  term?: {
    [key: string]: string;
  };
  bool?: QueryArray;
};

export type TermsQuery = {
  terms?: {
    [key: string]: string[];
  };
  bool?: QueryArray;
};

export type QueryArray = {
  must?: TermsQuery[];
  should?: TermsQuery[];
  filter?: TermQuery[];
  minimum_should_match?: number;
};

export type BooleanQuery = {
  bool: QueryArray;
};

export default BooleanQuery;
