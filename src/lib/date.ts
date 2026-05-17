/**
 * Helper to robustly parse a datetime string from Supabase (which might not have timezone suffix)
 * as a UTC Date object, matching how it was created.
 */
export function parseDateTime(dateInput: any): Date {
  if (!dateInput) return new Date();
  if (dateInput instanceof Date) return dateInput;
  
  if (typeof dateInput === 'string') {
    // Check if it already has timezone indicators
    const hasTimezone = dateInput.endsWith('Z') || 
                        /[-+]\d{2}:?\d{2}$/.test(dateInput) || 
                        dateInput.includes('GMT') || 
                        dateInput.includes('UTC');
                        
    if (!hasTimezone) {
      // Normalize standard SQL space spacer to 'T' and append 'Z' for UTC
      const normalized = dateInput.trim().replace(' ', 'T');
      return new Date(normalized + 'Z');
    }
  }
  
  return new Date(dateInput);
}
