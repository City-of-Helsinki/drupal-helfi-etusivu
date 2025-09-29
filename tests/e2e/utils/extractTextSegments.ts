/**
 * Extracts text content from HTML, preserving text from all elements
 * and returning an array of non-empty text segments.
 */
const extractTextSegments = (html: string | null | undefined): string[] => {
  if (!html) return [];

  // Remove HTML tags retaining the text content.
  const text = html
    // Replace block elements with newlines to preserve text separation.
    .replace(/<(p|div|h[1-6]|ul|ol|li|br)[^>]*>/gi, '\n')
    // Remove all other HTML tags.
    .replace(/<[^>]+>/g, '')
    // Decode HTML entities.
    .replace(/&[a-z]+;/g, ' ')
    // Replace multiple spaces/newlines with a single space.
    .replace(/\s+/g, ' ')
    .trim();

  // Split by common sentence/segment terminators and filter out empty strings.
  return text
    .split(/(?<=[.!?])\s+|\n+/)
    .map(s => s.trim())
    .filter(s => s.length > 0);
};

export {
  extractTextSegments,
};
