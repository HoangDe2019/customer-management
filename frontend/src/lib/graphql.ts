import { GraphQLClient } from 'graphql-request';

const apiBase = import.meta.env.VITE_API_URL || '/api';
const graphqlUrl = import.meta.env.VITE_GRAPHQL_URL || apiBase + '/graphql';

export const graphqlClient = new GraphQLClient(graphqlUrl, {
  headers: () => {
    const token = sessionStorage.getItem('access_token');
    return {
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    };
  },
});

export async function graphqlRequest<T>(
  document: string,
  variables?: Record<string, unknown>
): Promise<T> {
  return graphqlClient.request<T>(document, variables);
}
