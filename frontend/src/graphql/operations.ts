/** GraphQL query & mutation documents - dùng cho request → notification */

export const QUERY_ME = /* GraphQL */ `
  query Me {
    me { id name email role is_admin }
  }
`;

export const QUERY_CONFIG = /* GraphQL */ `
  query Config {
    config {
      version default_pos_fee default_agent_fee currency timezone
      status_values amount_suggestions
    }
  }
`;

export const QUERY_AGENTS = /* GraphQL */ `
  query Agents($status: String) {
    agents(status: $status) { id agent_id name status allowed_users }
  }
`;

export const QUERY_USER_AGENTS = /* GraphQL */ `
  query UserAgents {
    userAgents { id agent_id name status }
  }
`;

export const QUERY_TRANSACTIONS = /* GraphQL */ `
  query Transactions(
    $agent_id: Int $status: String $date_from: String $date_to: String
    $transaction_type: String $page: Int $per_page: Int
  ) {
    transactions(
      agent_id: $agent_id status: $status date_from: $date_from date_to: $date_to
      transaction_type: $transaction_type page: $page per_page: $per_page
    ) {
      data { id transaction_id agent_id customer_name total_amount transaction_type status transaction_date agent { id name } }
      current_page last_page per_page total
    }
  }
`;

export const QUERY_TRANSACTION = /* GraphQL */ `
  query Transaction($id: Int!) {
    transaction(id: $id) {
      id transaction_id agent_id customer_name cccd_number total_amount
      transaction_type status transaction_date pos_fee_amount agent_fee_amount
      profit agent_advance refund_to_agent net_settlement
      agent { id name } user { id name }
    }
  }
`;

export const QUERY_STATISTICS = /* GraphQL */ `
  query Statistics($agent_id: Int!, $period: String) {
    statistics(agent_id: $agent_id, period: $period) {
      total_transactions total_amount total_profit total_agent_advance
      dao_count rut_count recent { name amount type date status } by_status
    }
  }
`;

export const QUERY_SUMMARY = /* GraphQL */ `
  query Summary {
    summary { agent_id name total_transactions total_amount total_profit }
  }
`;

export const QUERY_DAILY_ADVANCES = /* GraphQL */ `
  query DailyAdvances($agent_id: Int!, $date: String!) {
    dailyAdvances(agent_id: $agent_id, date: $date) { value }
  }
`;

export const QUERY_EOD_SETTLEMENT = /* GraphQL */ `
  query EodSettlement($agent_id: Int!, $date: String!) {
    eodSettlement(agent_id: $agent_id, date: $date) { value }
  }
`;

export const QUERY_SETTLEMENT_HISTORY = /* GraphQL */ `
  query SettlementHistory($agent_id: Int, $page: Int, $per_page: Int) {
    settlementHistory(agent_id: $agent_id, page: $page, per_page: $per_page) {
      data { id agent_id settlement_date agent { name } settler { name } }
      current_page last_page per_page total
    }
  }
`;

export const QUERY_QUEUE_STATUS = /* GraphQL */ `
  query QueueStatus {
    queueStatus { connection driver pending_count message }
  }
`;

export const QUERY_CLONE_STATUS = /* GraphQL */ `
  query CloneStatus {
    cloneStatus { last_run { status message started_at finished_at exit_code } }
  }
`;

export const QUERY_MOMO_CHECK_STATUS = /* GraphQL */ `
  query MomoCheckStatus($orderId: String!) {
    momoCheckStatus(orderId: $orderId) { status resultCode message }
  }
`;

// Mutations (return RequestAck; result via notification)
export const MUTATION_LOGOUT = /* GraphQL */ `
  mutation Logout { logout { accepted request_id message } }
`;

export const MUTATION_CREATE_AGENT = /* GraphQL */ `
  mutation CreateAgent($name: String!, $allowed_users: String) {
    createAgent(name: $name, allowed_users: $allowed_users) { accepted request_id message }
  }
`;

export const MUTATION_UPDATE_AGENT = /* GraphQL */ `
  mutation UpdateAgent($id: Int!, $status: String, $allowed_users: String) {
    updateAgent(id: $id, status: $status, allowed_users: $allowed_users) { accepted request_id message }
  }
`;

export const MUTATION_DELETE_AGENT = /* GraphQL */ `
  mutation DeleteAgent($id: Int!) {
    deleteAgent(id: $id) { accepted request_id message }
  }
`;

export const MUTATION_CREATE_TRANSACTION = /* GraphQL */ `
  mutation CreateTransaction(
    $agent_id: Int! $customer_name: String $cccd_number: String $total_amount: Float!
    $transaction_type: String! $pos_fee_percent: Float $agent_fee_percent: Float $agent_advance: Float
  ) {
    createTransaction(
      agent_id: $agent_id customer_name: $customer_name cccd_number: $cccd_number total_amount: $total_amount
      transaction_type: $transaction_type pos_fee_percent: $pos_fee_percent agent_fee_percent: $agent_fee_percent agent_advance: $agent_advance
    ) { accepted request_id message }
  }
`;

export const MUTATION_UPDATE_TRANSACTION_STATUS = /* GraphQL */ `
  mutation UpdateTransactionStatus($id: Int!, $status: String!) {
    updateTransactionStatus(id: $id, status: $status) { accepted request_id message }
  }
`;

export const MUTATION_SETTLE_DAILY_ADVANCES = /* GraphQL */ `
  mutation SettleDailyAdvances($agent_id: Int!, $date: String!) {
    settleDailyAdvances(agent_id: $agent_id, date: $date) { accepted request_id message }
  }
`;

export const MUTATION_SAVE_EOD_SETTLEMENT = /* GraphQL */ `
  mutation SaveEodSettlement($agent_id: Int!, $date: String!, $settlement_data: String!) {
    saveEodSettlement(agent_id: $agent_id, date: $date, settlement_data: $settlement_data) { accepted request_id message }
  }
`;

export const MUTATION_GENERATE_MOMO_QR = /* GraphQL */ `
  mutation GenerateMoMoQR($customer_name: String!, $total_amount: Float!, $transaction_type: String!) {
    generateMoMoQR(customer_name: $customer_name, total_amount: $total_amount, transaction_type: $transaction_type) { accepted request_id message }
  }
`;

export const MUTATION_SCAN_CCCD = /* GraphQL */ `
  mutation ScanCCCD($image: String!) {
    scanCCCD(image: $image) { accepted request_id message }
  }
`;

export const MUTATION_QUEUE_DISPATCH = /* GraphQL */ `
  mutation QueueDispatch($type: String!, $payload: String, $queue: String) {
    queueDispatch(type: $type, payload: $payload, queue: $queue) { accepted request_id message }
  }
`;

export const MUTATION_TRIGGER_CLONE = /* GraphQL */ `
  mutation TriggerClone { triggerClone { accepted request_id message } }
`;
