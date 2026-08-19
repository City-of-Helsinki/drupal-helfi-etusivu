type SearchResult = {
  bundle: string;
  content?: string;
  entity_type: string;
  fragment?: string | null;
  id: string;
  metatag_title: string;
  published_at?: number;
  score: number;
  title: string;
  url: string;
};

type Promotion = {
  description: string;
  score: number;
  title: string;
  url: string;
};

type SearchResponse = {
  debug?: {
    bundles: Record<string, number>;
  };
  low_relevance: boolean;
  page: number;
  promoted: Promotion[];
  results: SearchResult[];
  size: number;
  total_hits: number;
};

export type { Promotion, SearchResponse, SearchResult };
