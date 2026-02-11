import { api } from '../lib/api';
import type { MoMoQRResponse } from '../types';

export async function generateMoMoQR(payload: {
  customer_name: string;
  total_amount: number;
  transaction_type: 'Đáo' | 'Rút';
}): Promise<MoMoQRResponse> {
  const { data } = await api.post<MoMoQRResponse>('/momo/generate-qr', payload);
  return data;
}
