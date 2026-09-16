export interface PlatformStats {
  published_opportunities: number;
  countries: number;
  companies: number;
  verified_companies: number;
  by_category: {
    buying: number;
    selling: number;
    partnership: number;
  };
}
