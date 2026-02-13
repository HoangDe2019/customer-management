export { generateMoMoQR, checkMoMoStatus } from './graphqlApi';
export type { MoMoQRResponse } from '../types';

export interface MoMoCheckStatusResponse {
  status: string;
  resultCode?: number;
  message?: string;
}
