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

type ContactValue = {
  type: string | null;
  value: string;
};

type Contact = {
  addresses: ContactValue[];
  email: string | null;
  id: string;
  job_title: string | null;
  name: string;
  organization_hierarchy: string[];
  phones: ContactValue[];
  score: number;
  service_hours: string | null;
};

type SearchResponse = {
  // Contacts are only returned for logged-in users.
  // @todo https://helsinkisolutionoffice.atlassian.net/browse/UHF-13957.
  contacts: Contact[];
  contacts_total: number;
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

export type { Contact, Promotion, SearchResponse, SearchResult };
