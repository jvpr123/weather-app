export interface Coordinates {
  latitude: number;
  longitude: number;
}

export interface LocationData extends Coordinates {
  name: string;
  state: string | null;
  country: string;
}
