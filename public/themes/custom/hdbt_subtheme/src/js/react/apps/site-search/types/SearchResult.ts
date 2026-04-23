type SearchResult = {
  score: number;
  entity_type: string;
  bundle: string;
  url: string;
  title: string;
  language: string;
};

type Promotion = {
  title: string;
  description: string;
  url: string;
  language: string;
  score: number;
};

type SearchResponse = {
  promoted: Promotion[];
  results: SearchResult[];
};

export type { SearchResult, Promotion, SearchResponse };
