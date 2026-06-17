type SearchResult = {
  id: string;
  score: number;
  entity_type: string;
  bundle: string;
  url: string;
  title: string;
  metatag_title: string;
  published_at?: number;
  content?: string;
  fragment?: string | null;
};

type Promotion = {
  title: string;
  description: string;
  url: string;
  score: number;
};

type SearchResponse = {
  promoted: Promotion[];
  results: SearchResult[];
  page: number;
  size: number;
  total_hits: number;
  debug?: {
    bundles: Record<string, number>;
  };
};

export type { Promotion, SearchResponse, SearchResult };
