declare namespace drupalSettings {
  const helfi_site_search: {
    ai_register_url?: string;
    external_links?: {
      jobs: string;
      events: string;
      decisions: string;
      contact: string;
      helsinki_near_you: string;
    };
    search_url: string;
    // Contacts are shown only to logged-in users.
    // @todo https://helsinkisolutionoffice.atlassian.net/browse/UHF-13957.
    show_contacts?: boolean;
  };
}
